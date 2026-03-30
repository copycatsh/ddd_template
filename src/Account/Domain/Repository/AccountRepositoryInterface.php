<?php

namespace App\Account\Domain\Repository;

use App\Account\Domain\Entity\Account;

interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    public function findById(string $id): ?Account;
}
