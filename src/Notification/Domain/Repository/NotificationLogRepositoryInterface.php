<?php

namespace App\Notification\Domain\Repository;

use App\Notification\Domain\Entity\NotificationLog;

interface NotificationLogRepositoryInterface
{
    public function save(NotificationLog $log): void;
}
