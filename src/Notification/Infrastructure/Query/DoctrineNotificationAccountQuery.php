<?php

namespace App\Notification\Infrastructure\Query;

use App\Notification\Domain\Port\NotificationAccountData;
use App\Notification\Domain\Port\NotificationAccountQuery;
use Doctrine\DBAL\Connection;

class DoctrineNotificationAccountQuery implements NotificationAccountQuery
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByAccountId(string $accountId): ?NotificationAccountData
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, user_id, currency FROM account_projections WHERE id = :id',
            ['id' => $accountId]
        );

        if (false === $row) {
            return null;
        }

        return new NotificationAccountData(
            $row['id'],
            $row['user_id'],
            $row['currency'],
        );
    }
}
