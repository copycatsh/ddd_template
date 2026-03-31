<?php

namespace App\Shared\Integration\Event;

final readonly class TransactionCompletedIntegrationEvent
{
    public function __construct(
        public string $transactionId,
        public string $accountId,
    ) {
    }
}
