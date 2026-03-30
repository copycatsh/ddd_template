<?php

namespace App\Account\Domain\Repository;

use App\Account\Domain\Entity\EventSourcedAccount;

interface EventSourcedAccountRepositoryInterface
{
    public function save(EventSourcedAccount $account): void;

    public function findById(string $id): ?EventSourcedAccount;
}
