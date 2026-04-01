<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class UserHasActiveAccountsException extends DomainException
{
    public static function withId(string $userId): self
    {
        return new self(sprintf('Cannot delete user %s: user has active accounts', $userId));
    }
}
