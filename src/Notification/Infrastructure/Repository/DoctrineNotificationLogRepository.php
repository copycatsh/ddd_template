<?php

namespace App\Notification\Infrastructure\Repository;

use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineNotificationLogRepository implements NotificationLogRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(NotificationLog $log): void
    {
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
