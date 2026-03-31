<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331152811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update transactions schema (rename account_id, add to_account_id) and add composite index for daily transfer limit queries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions ADD to_account_id VARCHAR(50) DEFAULT NULL, CHANGE account_id from_account_id VARCHAR(50) NOT NULL');
        $this->addSql('CREATE INDEX idx_transactions_daily_activity ON transactions (from_account_id, status, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_transactions_daily_activity ON transactions');
        $this->addSql('ALTER TABLE transactions DROP to_account_id, CHANGE from_account_id account_id VARCHAR(50) NOT NULL');
    }
}
