<?php

namespace App\Shared\Domain\Exception;

class NegativeBalanceException extends DomainException
{
    public static function fromSubtraction(string $result): self
    {
        return new self(
            sprintf('Result cannot be negative, got: %s', $result)
        );
    }
}
