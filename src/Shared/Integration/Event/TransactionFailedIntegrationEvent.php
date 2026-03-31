<?php

namespace App\Shared\Integration\Event;

final readonly class TransactionFailedIntegrationEvent
{
    public function __construct(
        public string $transactionId,
        public string $accountId,
        public ?string $reason = null,
    ) {
    }
}
