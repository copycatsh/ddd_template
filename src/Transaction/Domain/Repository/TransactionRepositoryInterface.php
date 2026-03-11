<?php

namespace App\Transaction\Domain\Repository;

use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\ValueObject\TransactionStatus;

interface TransactionRepositoryInterface
{
    public function save(Transaction $transaction): void;

    public function findById(string $id): ?Transaction;

    public function findByAccountId(string $accountId): array;

    public function findByStatus(TransactionStatus $status): array;

    public function findPendingByAccountId(string $accountId): array;

    public function delete(Transaction $transaction): void;
}
