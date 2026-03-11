<?php

namespace App\Account\Domain\Event;

use App\Account\Domain\ValueObject\Currency;
use App\Shared\Domain\Event\AbstractDomainEvent;

class AccountCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $accountId,
        private string $userId,
        private Currency $currency,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->accountId;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getEventData(): array
    {
        return [
            'accountId' => $this->accountId,
            'userId' => $this->userId,
            'currency' => $this->currency->value,
        ];
    }
}
