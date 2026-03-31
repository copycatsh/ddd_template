<?php

namespace App\Shared\Integration;

use App\Shared\Domain\Event\DomainEventInterface;
use App\Shared\Integration\Event\TransactionCompletedIntegrationEvent;
use App\Shared\Integration\Event\TransactionCreatedIntegrationEvent;
use App\Shared\Integration\Event\TransactionFailedIntegrationEvent;
use App\Transaction\Domain\Event\TransactionCompletedEvent;
use App\Transaction\Domain\Event\TransactionCreatedEvent;
use App\Transaction\Domain\Event\TransactionFailedEvent;

class IntegrationEventMapper implements IntegrationEventMapperInterface
{
    public function map(DomainEventInterface $domainEvent): object
    {
        return match (true) {
            $domainEvent instanceof TransactionCreatedEvent => new TransactionCreatedIntegrationEvent(
                $domainEvent->getTransactionId(),
                $domainEvent->getAccountId(),
                $domainEvent->getAmount(),
                $domainEvent->getCurrency(),
            ),
            $domainEvent instanceof TransactionCompletedEvent => new TransactionCompletedIntegrationEvent(
                $domainEvent->getTransactionId(),
                $domainEvent->getAccountId(),
            ),
            $domainEvent instanceof TransactionFailedEvent => new TransactionFailedIntegrationEvent(
                $domainEvent->getTransactionId(),
                $domainEvent->getAccountId(),
                $domainEvent->getReason(),
            ),
            default => throw new \InvalidArgumentException(sprintf('Unsupported domain event: %s', $domainEvent::class)),
        };
    }
}
