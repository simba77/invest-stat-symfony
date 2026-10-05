<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Manual accounts start their journals from their deals with `accounts:rebuild`, run after this migration.
 */
final class Version20261004113520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep a journal of operations for manual accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE manual_operations (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, instrument_id INT DEFAULT NULL, type VARCHAR(32) NOT NULL, executed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ticker VARCHAR(64) DEFAULT NULL, stock_market VARCHAR(16) DEFAULT NULL, quantity INT DEFAULT NULL, price NUMERIC(18, 4) DEFAULT NULL, accrued_interest NUMERIC(18, 4) DEFAULT NULL, commission NUMERIC(18, 4) DEFAULT NULL, target_price NUMERIC(18, 4) DEFAULT NULL, lot VARCHAR(80) DEFAULT NULL, amount NUMERIC(18, 4) DEFAULT NULL, currency VARCHAR(8) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', INDEX IDX_20F9A9879B6B5FBA (account_id), INDEX IDX_20F9A987CF11D9C (instrument_id), INDEX manual_operation_executed_at (account_id, executed_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE manual_operations ADD CONSTRAINT FK_20F9A9879B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manual_operations ADD CONSTRAINT FK_20F9A987CF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id)');
        $this->addSql('ALTER TABLE accounts ADD journal_started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE manual_operations');
        $this->addSql('ALTER TABLE accounts DROP journal_started_at');
    }
}
