<?php

namespace App\User\Application\Command;

use App\User\Domain\ValueObject\UserRole;

class CreateUserCommand
{
    public function __construct(
        private string $email,
        private string $password,
        private UserRole $role = UserRole::USER,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }
}
