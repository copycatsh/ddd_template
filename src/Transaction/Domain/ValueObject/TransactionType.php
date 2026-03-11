<?php

namespace App\Transaction\Domain\ValueObject;

enum TransactionType: string
{
    case DEPOSIT = 'DEPOSIT';
    case WITHDRAWAL = 'WITHDRAWAL';
    case TRANSFER = 'TRANSFER';

    public function isDeposit(): bool
    {
        return self::DEPOSIT === $this;
    }

    public function isWithdrawal(): bool
    {
        return self::WITHDRAWAL === $this;
    }

    public function isTransfer(): bool
    {
        return self::TRANSFER === $this;
    }
}
