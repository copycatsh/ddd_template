<?php

namespace App\Account\Domain\Repository;

use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\ValueObject\Currency;

interface EventSourcedAccountRepositoryInterface
{
    public function save(EventSourcedAccount $account): void;

    public function findById(string $id): ?EventSourcedAccount;

    public function findByUserIdAndCurrency(string $userId, Currency $currency): ?EventSourcedAccount;

    public function findByUserId(string $userId): array;
}
