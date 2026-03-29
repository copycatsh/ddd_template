<?php

namespace App\Account\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class InvalidAmountException extends DomainException
{
    public static function mustBePositive(): self
    {
        return new self('Amount must be greater than zero');
    }
}
