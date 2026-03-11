<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311181614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification_log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification_log (
            id BIGINT AUTO_INCREMENT NOT NULL,
            transaction_id VARCHAR(50) NOT NULL,
            account_id VARCHAR(50) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            recipient_email VARCHAR(255) NOT NULL,
            notification_type VARCHAR(50) NOT NULL,
            sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_notification_log_transaction_id (transaction_id),
            INDEX idx_notification_log_user_id (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification_log');
    }
}
