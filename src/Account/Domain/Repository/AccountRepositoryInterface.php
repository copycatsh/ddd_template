<?php

namespace App\Account\Domain\Repository;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\ValueObject\Currency;

interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    public function findById(string $id): ?Account;

    public function findByUserIdAndCurrency(string $userId, Currency $currency): ?Account;

    public function findByUserId(string $userId): array;
}