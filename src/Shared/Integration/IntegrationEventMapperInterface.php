<?php

namespace App\Shared\Integration;

use App\Shared\Domain\Event\DomainEventInterface;

interface IntegrationEventMapperInterface
{
    public function map(DomainEventInterface $domainEvent): object;
}
