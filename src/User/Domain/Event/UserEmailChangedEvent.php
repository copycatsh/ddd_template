<?php

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use App\User\Domain\ValueObject\Email;

class UserEmailChangedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $userId,
        private Email $oldEmail,
        private Email $newEmail,
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

    public function getOldEmail(): Email
    {
        return $this->oldEmail;
    }

    public function getNewEmail(): Email
    {
        return $this->newEmail;
    }

    public function getEventData(): array
    {
        return [
            'userId' => $this->userId,
            'oldEmail' => $this->oldEmail->getValue(),
            'newEmail' => $this->newEmail->getValue(),
        ];
    }
}
