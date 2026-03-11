<?php

namespace App\Account\Domain\Event;

use App\Account\Domain\ValueObject\Money;
use App\Shared\Domain\Event\AbstractDomainEvent;

class MoneyDepositedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $accountId,
        private Money $amount,
        private string $newBalance,
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

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getNewBalance(): string
    {
        return $this->newBalance;
    }

    public function getEventData(): array
    {
        return [
            'accountId' => $this->accountId,
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency()->value,
            'newBalance' => $this->newBalance,
        ];
    }
}
