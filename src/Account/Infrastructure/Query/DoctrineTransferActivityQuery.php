<?php

namespace App\Account\Infrastructure\Query;

use App\Account\Domain\Port\TransferActivityData;
use App\Account\Domain\Port\TransferActivityQuery;
use Doctrine\DBAL\Connection;

class DoctrineTransferActivityQuery implements TransferActivityQuery
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getDailyActivity(string $accountId, \DateTimeImmutable $date): TransferActivityData
    {
        $startOfDay = $date->setTime(0, 0, 0);
        $nextDayStart = $date->modify('+1 day')->setTime(0, 0, 0);

        try {
            $row = $this->connection->fetchAssociative(
                'SELECT COALESCE(SUM(amount), 0) as daily_total, COUNT(*) as daily_count
                 FROM transactions
                 WHERE from_account_id = :accountId
                   AND status = :status
                   AND type = :type
                   AND created_at >= :start
                   AND created_at < :end',
                [
                    'accountId' => $accountId,
                    'status' => 'COMPLETED',
                    'type' => 'TRANSFER',
                    'start' => $startOfDay->format('Y-m-d H:i:s'),
                    'end' => $nextDayStart->format('Y-m-d H:i:s'),
                ]
            );

            return new TransferActivityData(
                $row['daily_total'] ?? '0.00',
                (int) ($row['daily_count'] ?? 0),
                $date,
            );
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new \RuntimeException('Failed to query transfer activity: '.$e->getMessage(), 0, $e);
        }
    }
}
