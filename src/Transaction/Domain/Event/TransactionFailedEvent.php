<?php

namespace App\Transaction\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

class TransactionFailedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $transactionId,
        private string $accountId,
        private ?string $reason = null,
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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getEventData(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'accountId' => $this->accountId,
            'reason' => $this->reason,
        ];
    }
}
