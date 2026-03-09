<?php

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Port\AccountBalanceData;
use App\Account\Domain\Port\AccountReadModelQuery;
use App\Account\Domain\Port\AccountSummaryData;
use Doctrine\DBAL\Connection;

class DoctrineAccountReadModelQuery implements AccountReadModelQuery
{
    public function __construct(
        private Connection $connection
    ) {}

    public function getAccountBalance(string $accountId): ?AccountBalanceData
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, balance, currency, updated_at FROM accounts WHERE id = :id',
            ['id' => $accountId]
        );

        if ($row === false) {
            return null;
        }

        return new AccountBalanceData(
            $row['id'],
            $row['balance'],
            $row['currency'],
            new \DateTimeImmutable($row['updated_at'])
        );
    }

    public function getUserAccountsSummary(string $userId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, balance, currency, created_at FROM accounts WHERE user_id = :userId',
            ['userId' => $userId]
        );

        return array_map(
            fn(array $row) => new AccountSummaryData(
                $row['id'],
                $row['balance'],
                $row['currency'],
                new \DateTimeImmutable($row['created_at'])
            ),
            $rows
        );
    }
}
