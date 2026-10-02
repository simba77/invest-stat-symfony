<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002185330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link accounts to broker accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE broker_account_links (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, provider VARCHAR(32) NOT NULL, encrypted_token LONGTEXT NOT NULL, external_account_id VARCHAR(64) NOT NULL, external_account_name VARCHAR(255) NOT NULL, external_account_opened_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', fee_allocation VARCHAR(32) NOT NULL, enabled TINYINT(1) NOT NULL, last_synced_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_sync_status VARCHAR(16) DEFAULT NULL, last_sync_message LONGTEXT DEFAULT NULL, last_sync_discrepancies JSON NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_459AEFDB9B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE broker_account_links ADD CONSTRAINT FK_459AEFDB9B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE broker_account_links DROP FOREIGN KEY FK_459AEFDB9B6B5FBA');
        $this->addSql('DROP TABLE broker_account_links');
    }
}
