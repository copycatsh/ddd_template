<?php

namespace App\Account\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\ValueObject\Currency;

class AccountAlreadyExistsException extends DomainException
{
    public static function forUserAndCurrency(string $userId, Currency $currency): self
    {
        return new self(
            sprintf(
                'Account already exists for user %s with currency %s',
                $userId,
                $currency->value
            )
        );
    }
}
