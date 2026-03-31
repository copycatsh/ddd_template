<?php

namespace App\Shared\Integration\Event;

final readonly class TransactionCreatedIntegrationEvent
{
    public function __construct(
        public string $transactionId,
        public string $accountId,
        public string $amount,
        public string $currency,
    ) {
    }
}
