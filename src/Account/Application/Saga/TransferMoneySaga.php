<?php

namespace App\Account\Application\Saga;

use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use App\Account\Domain\ValueObject\Money;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Event\TransactionCompletedEvent;
use App\Transaction\Domain\Event\TransactionCreatedEvent;
use App\Transaction\Domain\Event\TransactionFailedEvent;
use App\Transaction\Domain\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\TransactionType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class TransferMoneySaga
{
    public function __construct(
        private readonly EventSourcedAccountRepositoryInterface $accountRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly Connection $connection,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Transfer money between two accounts using Event Sourcing.
     *
     * Uses DBAL transaction to wrap all saves (event store + transaction record)
     * for ACID guarantees. Since both the EventStore and TransactionRepository
     * share the same database connection, a single DBAL transaction ensures
     * all-or-nothing semantics.
     *
     * NOTE: In a distributed system with separate databases per bounded context,
     * you would use compensating events instead of a shared DB transaction:
     * - If deposit fails after successful withdraw, record a compensating
     *   deposit event on the source account to restore funds.
     * - See Phase 6 TODO for full saga state machine pattern.
     *
     * @throws \DomainException  if validation failed
     * @throws \RuntimeException if the operation failed
     */
    public function execute(
        string $fromAccountId,
        string $toAccountId,
        Money $amount,
    ): string {
        if ($fromAccountId === $toAccountId) {
            throw new \DomainException('Cannot transfer to the same account');
        }

        $fromAccount = $this->accountRepository->findById($fromAccountId);
        $toAccount = $this->accountRepository->findById($toAccountId);

        if (!$fromAccount) {
            throw new \DomainException("Source account {$fromAccountId} not found");
        }

        if (!$toAccount) {
            throw new \DomainException("Destination account {$toAccountId} not found");
        }

        if (!$fromAccount->getCurrency()->equals($toAccount->getCurrency())) {
            throw new \DomainException('Cannot transfer between different currencies');
        }

        if (!$amount->getCurrency()->equals($fromAccount->getCurrency())) {
            throw new \DomainException('Amount currency must match account currency');
        }

        $transactionId = Uuid::v4()->toRfc4122();

        $this->connection->beginTransaction();

        try {
            $transaction = new Transaction(
                $transactionId,
                $fromAccountId,
                $toAccountId,
                TransactionType::TRANSFER,
                $amount
            );
            $this->transactionRepository->save($transaction);

            $fromAccount->withdraw($amount);
            $this->accountRepository->save($fromAccount);

            $toAccount->deposit($amount);
            $this->accountRepository->save($toAccount);

            $transaction->complete();
            $this->transactionRepository->save($transaction);

            $this->connection->commit();

            $this->messageBus->dispatch(new TransactionCreatedEvent(
                $transactionId,
                $fromAccountId,
                TransactionType::TRANSFER,
                $amount->getAmount(),
                $amount->getCurrency()->value,
            ));

            $this->messageBus->dispatch(new TransactionCompletedEvent(
                $transactionId,
                $fromAccountId,
            ));

            return $transactionId;
        } catch (\Exception $e) {
            try {
                $this->connection->rollBack();
            } catch (\Throwable) {
                // Rollback failure must not hide the original exception
            }

            try {
                $this->messageBus->dispatch(new TransactionFailedEvent(
                    $transactionId,
                    $fromAccountId,
                    $e->getMessage(),
                ));
            } catch (\Throwable) {
                // Dispatch failure must not hide the original exception
            }

            if ($e instanceof \DomainException) {
                throw $e;
            }

            throw new \RuntimeException("Transfer failed: {$e->getMessage()}", 0, $e);
        }
    }
}
