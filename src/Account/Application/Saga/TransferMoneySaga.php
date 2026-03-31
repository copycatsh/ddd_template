<?php

namespace App\Account\Application\Saga;

use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\Service\MoneyTransferDomainService;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Integration\IntegrationEventMapperInterface;
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
        private readonly IntegrationEventMapperInterface $integrationEventMapper,
    ) {
    }

    /**
     * Transfer money between two accounts using Event Sourcing.
     *
     * Domain validation is delegated to MoneyTransferDomainService.
     * Cross-BC communication uses Integration Events (Published Language pattern):
     * domain events are created, mapped to integration events, then dispatched
     * via Messenger. Notification BC consumes integration events only.
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

            $this->messageBus->dispatch(
                $this->integrationEventMapper->map(new TransactionCreatedEvent(
                    $transactionId,
                    $fromAccountId,
                    TransactionType::TRANSFER,
                    $amount->getAmount(),
                    $amount->getCurrency()->value,
                ))
            );

            $this->messageBus->dispatch(
                $this->integrationEventMapper->map(new TransactionCompletedEvent(
                    $transactionId,
                    $fromAccountId,
                ))
            );

            return $transactionId;
        } catch (\Exception $e) {
            try {
                $this->connection->rollBack();
            } catch (\Throwable) {
                // Rollback failure must not hide the original exception
            }

            try {
                $this->messageBus->dispatch(
                    $this->integrationEventMapper->map(new TransactionFailedEvent(
                        $transactionId,
                        $fromAccountId,
                        $e->getMessage(),
                    ))
                );
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
