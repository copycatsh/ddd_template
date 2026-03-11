<?php

namespace App\Transaction\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class TransactionNotFoundException extends DomainException
{
    public static function withId(string $transactionId): self
    {
        return new self(
            sprintf('Transaction with ID "%s" not found', $transactionId)
        );
    }

    public static function forAccount(string $accountId): self
    {
        return new self(
            sprintf('No transactions found for account "%s"', $accountId)
        );
    }
}
