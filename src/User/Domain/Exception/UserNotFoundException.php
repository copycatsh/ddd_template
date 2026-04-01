<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class UserNotFoundException extends DomainException
{
    public static function withId(string $userId): self
    {
        return new self(
            sprintf('User with ID %s not found', $userId)
        );
    }
}
