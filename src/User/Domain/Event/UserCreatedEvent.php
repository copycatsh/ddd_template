<?php

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use App\User\Domain\ValueObject\UserRole;

class UserCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $userId,
        private string $email,
        private UserRole $role,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getEventData(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
            'role' => $this->role->value,
        ];
    }
}
