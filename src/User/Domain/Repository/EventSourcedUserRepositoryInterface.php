<?php

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\EventSourcedUser;

interface EventSourcedUserRepositoryInterface
{
    public function save(EventSourcedUser $user): void;

    public function findById(string $id): ?EventSourcedUser;

    public function findByEmail(string $email): ?EventSourcedUser;
}
