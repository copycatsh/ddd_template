<?php

namespace App\User\Application\Command;

use App\User\Domain\ValueObject\Email;

class ChangeUserEmailCommand
{
    public function __construct(
        private string $userId,
        private Email $newEmail,
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getNewEmail(): Email
    {
        return $this->newEmail;
    }
}
