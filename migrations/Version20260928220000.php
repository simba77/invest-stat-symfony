<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete deposits together with their deposit account';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deposits DROP FOREIGN KEY FK_449E9C9E6E60BC73');
        $this->addSql('ALTER TABLE deposits ADD CONSTRAINT FK_449E9C9E6E60BC73 FOREIGN KEY (deposit_account_id) REFERENCES deposit_accounts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deposits DROP FOREIGN KEY FK_449E9C9E6E60BC73');
        $this->addSql('ALTER TABLE deposits ADD CONSTRAINT FK_449E9C9E6E60BC73 FOREIGN KEY (deposit_account_id) REFERENCES deposit_accounts (id)');
    }
}
