<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002185923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Journal of operations received from brokers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE broker_operations (id INT AUTO_INCREMENT NOT NULL, link_id INT NOT NULL, external_id VARCHAR(64) NOT NULL, parent_external_id VARCHAR(64) DEFAULT NULL, type VARCHAR(32) NOT NULL, raw_type VARCHAR(64) NOT NULL, state VARCHAR(16) NOT NULL, executed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', instrument_uid VARCHAR(64) DEFAULT NULL, instrument_kind VARCHAR(16) DEFAULT NULL, ticker VARCHAR(64) DEFAULT NULL, class_code VARCHAR(32) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, quantity BIGINT NOT NULL, price NUMERIC(24, 9) DEFAULT NULL, payment NUMERIC(24, 9) NOT NULL, currency VARCHAR(8) NOT NULL, commission NUMERIC(24, 9) DEFAULT NULL, accrued_interest NUMERIC(24, 9) DEFAULT NULL, description LONGTEXT DEFAULT NULL, payload JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_18D6F64EADA40271 (link_id), INDEX broker_operation_executed_at (link_id, executed_at), UNIQUE INDEX broker_operation_external_id (link_id, external_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE broker_operations ADD CONSTRAINT FK_18D6F64EADA40271 FOREIGN KEY (link_id) REFERENCES broker_account_links (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE broker_operations DROP FOREIGN KEY FK_18D6F64EADA40271');
        $this->addSql('DROP TABLE broker_operations');
    }
}
