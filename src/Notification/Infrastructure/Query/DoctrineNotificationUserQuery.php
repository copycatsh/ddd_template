<?php

namespace App\Notification\Infrastructure\Query;

use App\Notification\Domain\Port\NotificationUserData;
use App\Notification\Domain\Port\NotificationUserQuery;
use Doctrine\DBAL\Connection;

class DoctrineNotificationUserQuery implements NotificationUserQuery
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByUserId(string $userId): ?NotificationUserData
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email FROM users WHERE id = :id',
            ['id' => $userId]
        );

        if (false === $row) {
            return null;
        }

        return new NotificationUserData(
            $row['id'],
            $row['email'],
        );
    }
}
