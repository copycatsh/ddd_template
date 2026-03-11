<?php

namespace App\Account\Application\Saga;

use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\ValueObject\Money;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class TransferMoneySaga
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Transfer money between two accounts.
     *
     * Uses DB Transaction for ACID guarantees
     *
     * @throws \DomainException  if validation failed
     * @throws \RuntimeException if the operation failed
     */
    public function execute(
        string $fromAccountId,
        string $toAccountId,
        Money $amount,
    ): string {
        // Validation: you cannot translate for yourself
        if ($fromAccountId === $toAccountId) {
            throw new \DomainException('Cannot transfer to the same account');
        }

        // Download both accounts
        $fromAccount = $this->accountRepository->findById($fromAccountId);
        $toAccount = $this->accountRepository->findById($toAccountId);

        if (!$fromAccount) {
            throw new \DomainException("Source account {$fromAccountId} not found");
        }

        if (!$toAccount) {
            throw new \DomainException("Destination account {$toAccountId} not found");
        }

        // Validation: same currency between accounts
        if (!$fromAccount->getCurrency()->equals($toAccount->getCurrency())) {
            throw new \DomainException('Cannot transfer between different currencies');
        }

        // Validation: the currency amount matches the currency of the account
        if (!$amount->getCurrency()->equals($fromAccount->getCurrency())) {
            throw new \DomainException('Amount currency must match account currency');
        }

        // Start DB transaction - all or nothing (ACID)
        $this->entityManager->beginTransaction();

        try {
            // Step 1: Create a transaction record (PENDING)
            $transactionId = Uuid::v4()->toRfc4122();
            $transaction = new Transaction(
                $transactionId,
                $fromAccountId,
                $toAccountId,
                TransactionType::TRANSFER,
                $amount
            );
            $this->transactionRepository->save($transaction);

            // Step 2: Withdraw з source account
            $fromAccount->withdraw($amount);
            $this->accountRepository->save($fromAccount);

            // Step 3: Deposit на destination account
            $toAccount->deposit($amount);
            $this->accountRepository->save($toAccount);

            // Step 4: Complete transaction
            $transaction->complete();
            $this->transactionRepository->save($transaction);

            // Commit - apply all changes together
            $this->entityManager->commit();

            return $transactionId;
        } catch (\Exception $e) {
            // Rollback - automatically roll back ALL changes
            $this->entityManager->rollback();

            // Re-throw with context
            throw new \RuntimeException("Transfer failed: {$e->getMessage()}", 0, $e);
        }
    }
}
