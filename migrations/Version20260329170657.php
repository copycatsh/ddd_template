<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260329170657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint on (user_id, currency) for accounts table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CAC89EAC64B64DCC6956883F ON accounts (user_id, currency)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CAC89EAC64B64DCC6956883F ON accounts');
    }
}
