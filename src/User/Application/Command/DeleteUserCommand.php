<?php

namespace App\User\Application\Command;

class DeleteUserCommand
{
    public function __construct(
        private string $userId,
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
