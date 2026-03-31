<?php

namespace App\Account\Application\Saga;

use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\Service\MoneyTransferDomainService;
use App\Shared\Domain\ValueObject\Money;
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
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly Connection $connection,
        private readonly MessageBusInterface $messageBus,
        private readonly MoneyTransferDomainService $domainService,
    ) {
    }

    /**
     * Transfer money between two accounts using Event Sourcing.
     *
     * Domain validation (same-account, currency match, transfer limits) is
     * delegated to MoneyTransferDomainService. This saga is a pure orchestrator:
     * load accounts, validate, execute within DBAL transaction, dispatch events.
     *
     * @throws \DomainException  if validation failed (account not found)
     * @throws \RuntimeException if the operation failed
     */
    public function execute(
        string $fromAccountId,
        string $toAccountId,
        Money $amount,
    ): string {
        $fromAccount = $this->accountRepository->findById($fromAccountId);
        $toAccount = $this->accountRepository->findById($toAccountId);

        if (!$fromAccount) {
            throw new \DomainException("Source account {$fromAccountId} not found");
        }

        if (!$toAccount) {
            throw new \DomainException("Destination account {$toAccountId} not found");
        }

        $this->domainService->validate($fromAccount, $toAccount, $amount);

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
