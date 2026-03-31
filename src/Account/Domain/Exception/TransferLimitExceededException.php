<?php

namespace App\Account\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class TransferLimitExceededException extends DomainException
{
    public static function dailyLimitExceeded(
        string $accountId,
        string $dailyTotal,
        string $attemptedAmount,
        string $dailyLimit,
    ): self {
        return new self(
            sprintf(
                'Daily transfer limit exceeded for account %s. Limit: %s, Already transferred: %s, Attempted: %s',
                $accountId,
                $dailyLimit,
                $dailyTotal,
                $attemptedAmount,
            )
        );
    }
}
