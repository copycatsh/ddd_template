<?php

namespace App\Transaction\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Transaction\Domain\ValueObject\TransactionType;

class TransactionCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $transactionId,
        private string $accountId,
        private TransactionType $type,
        private string $amount,
        private string $currency,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->transactionId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getEventData(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'accountId' => $this->accountId,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
