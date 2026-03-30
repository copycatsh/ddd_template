<?php

namespace App\Shared\Domain\Exception;

class InvalidAmountException extends DomainException
{
    public static function negativeAmount(): self
    {
        return new self('Amount cannot be negative');
    }

    public static function mustBePositive(): self
    {
        return new self('Amount must be greater than zero');
    }
}
