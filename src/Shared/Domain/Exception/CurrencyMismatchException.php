<?php

namespace App\Shared\Domain\Exception;

use App\Shared\Domain\ValueObject\Currency;

class CurrencyMismatchException extends DomainException
{
    public static function forOperation(Currency $accountCurrency, Currency $operationCurrency): self
    {
        return new self(
            sprintf(
                'Currency mismatch. Account currency: %s, Operation currency: %s',
                $accountCurrency->value,
                $operationCurrency->value
            )
        );
    }
}
