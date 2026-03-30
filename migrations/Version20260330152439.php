<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330152439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create account_projections table and backfill from event_store';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE account_projections (
                id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                currency VARCHAR(3) NOT NULL,
                balance VARCHAR(20) NOT NULL DEFAULT \'0.00\',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX uniq_user_currency (user_id, currency),
                INDEX idx_user_id (user_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // Backfill Step 1: Insert all accounts from AccountCreatedEvent
        $this->addSql("
            INSERT INTO account_projections (id, user_id, currency, balance, created_at, updated_at)
            SELECT
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.accountId')),
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.userId')),
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.currency')),
                '0.00',
                occurred_at,
                occurred_at
            FROM event_store
            WHERE event_type = 'App\\\\Account\\\\Domain\\\\Event\\\\AccountCreatedEvent'
            ORDER BY version ASC
        ");

        // Backfill Step 2: Update balances from latest deposit/withdrawal events
        $this->addSql("
            UPDATE account_projections ap
            INNER JOIN (
                SELECT
                    aggregate_id,
                    JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.newBalance')) as final_balance,
                    occurred_at as last_updated
                FROM event_store e1
                WHERE event_type IN (
                    'App\\\\Account\\\\Domain\\\\Event\\\\MoneyDepositedEvent',
                    'App\\\\Account\\\\Domain\\\\Event\\\\MoneyWithdrawnEvent'
                )
                AND version = (
                    SELECT MAX(version) FROM event_store e2
                    WHERE e2.aggregate_id = e1.aggregate_id
                    AND e2.event_type IN (
                        'App\\\\Account\\\\Domain\\\\Event\\\\MoneyDepositedEvent',
                        'App\\\\Account\\\\Domain\\\\Event\\\\MoneyWithdrawnEvent'
                    )
                )
            ) latest ON ap.id = latest.aggregate_id
            SET ap.balance = latest.final_balance,
                ap.updated_at = latest.last_updated
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE account_projections');
    }
}
