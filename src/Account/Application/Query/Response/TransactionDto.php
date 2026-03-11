<?php

namespace App\Account\Application\Query\Response;

use App\Transaction\Domain\Entity\Transaction;

class TransactionDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $fromAccountId,
        public readonly ?string $toAccountId,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $completedAt,
    ) {
    }

    public static function fromEntity(Transaction $transaction): self
    {
        return new self(
            $transaction->getId(),
            $transaction->getType()->value,
            $transaction->getFromAccountId(),
            $transaction->getToAccountId(),
            $transaction->getAmount(),
            $transaction->getCurrency(),
            $transaction->getStatus()->value,
            $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
            $transaction->getCompletedAt()?->format('Y-m-d H:i:s')
        );
    }
}
