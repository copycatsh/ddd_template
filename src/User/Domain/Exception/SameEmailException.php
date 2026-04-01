<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class SameEmailException extends DomainException
{
    public static function create(): self
    {
        return new self('New email must be different from current email');
    }
}
