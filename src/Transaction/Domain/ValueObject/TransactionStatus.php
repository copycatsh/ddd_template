<?php

namespace App\Transaction\Domain\ValueObject;

enum TransactionStatus: string
{
    case PENDING = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';

    public function isPending(): bool
    {
        return self::PENDING === $this;
    }

    public function isCompleted(): bool
    {
        return self::COMPLETED === $this;
    }

    public function isFailed(): bool
    {
        return self::FAILED === $this;
    }
}
