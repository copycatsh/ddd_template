<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\Projection;

use App\Account\Domain\Port\AccountProjectionData;
use App\Account\Domain\Port\AccountProjectionQuery;
use Doctrine\DBAL\Connection;

class DoctrineAccountProjectionQuery implements AccountProjectionQuery
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByAccountId(string $accountId): ?AccountProjectionData
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, user_id, currency, balance, created_at, updated_at FROM account_projections WHERE id = :id',
            ['id' => $accountId]
        );

        if (false === $row) {
            return null;
        }

        return $this->mapToData($row);
    }

    public function findByUserIdAndCurrency(string $userId, string $currency): ?AccountProjectionData
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, user_id, currency, balance, created_at, updated_at FROM account_projections WHERE user_id = :userId AND currency = :currency',
            ['userId' => $userId, 'currency' => $currency]
        );

        if (false === $row) {
            return null;
        }

        return $this->mapToData($row);
    }

    /** @return AccountProjectionData[] */
    public function findByUserId(string $userId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, user_id, currency, balance, created_at, updated_at FROM account_projections WHERE user_id = :userId ORDER BY created_at ASC',
            ['userId' => $userId]
        );

        return array_map(fn (array $row) => $this->mapToData($row), $rows);
    }

    /** @param array<string, string> $row */
    private function mapToData(array $row): AccountProjectionData
    {
        return new AccountProjectionData(
            $row['id'],
            $row['user_id'],
            $row['currency'],
            $row['balance'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at']),
        );
    }
}
