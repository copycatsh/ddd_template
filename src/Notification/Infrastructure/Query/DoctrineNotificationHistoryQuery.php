<?php

namespace App\Notification\Infrastructure\Query;

use App\Notification\Domain\Port\NotificationHistoryData;
use App\Notification\Domain\Port\NotificationHistoryQuery;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class DoctrineNotificationHistoryQuery implements NotificationHistoryQuery
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return NotificationHistoryData[]
     */
    public function getByUserId(string $userId, int $page = 1, int $perPage = 20): array
    {
        $sql = 'SELECT id, transaction_id, account_id, user_id, recipient_email, notification_type, sent_at
                FROM notification_log
                WHERE user_id = :userId
                ORDER BY sent_at DESC
                LIMIT :limit OFFSET :offset';

        $rows = $this->connection->fetchAllAssociative($sql, [
            'userId' => $userId,
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ], [
            'userId' => ParameterType::STRING,
            'limit' => ParameterType::INTEGER,
            'offset' => ParameterType::INTEGER,
        ]);

        return array_map(
            fn (array $row) => new NotificationHistoryData(
                (int) $row['id'],
                $row['transaction_id'],
                $row['account_id'],
                $row['user_id'],
                $row['recipient_email'],
                $row['notification_type'],
                new \DateTimeImmutable($row['sent_at']),
            ),
            $rows
        );
    }

    public function countByUserId(string $userId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM notification_log WHERE user_id = :userId',
            ['userId' => $userId],
        );
    }
}
