<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250222190144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deals ADD share_id INT DEFAULT NULL, ADD bond_id INT DEFAULT NULL, ADD future_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deals ADD CONSTRAINT FK_EF39849B2AE63FDB FOREIGN KEY (share_id) REFERENCES shares (id)');
        $this->addSql('ALTER TABLE deals ADD CONSTRAINT FK_EF39849B73A18A67 FOREIGN KEY (bond_id) REFERENCES bonds (id)');
        $this->addSql('ALTER TABLE deals ADD CONSTRAINT FK_EF39849B78E2B382 FOREIGN KEY (future_id) REFERENCES futures (id)');
        $this->addSql('CREATE INDEX IDX_EF39849B2AE63FDB ON deals (share_id)');
        $this->addSql('CREATE INDEX IDX_EF39849B73A18A67 ON deals (bond_id)');
        $this->addSql('CREATE INDEX IDX_EF39849B78E2B382 ON deals (future_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deals DROP FOREIGN KEY FK_EF39849B2AE63FDB');
        $this->addSql('ALTER TABLE deals DROP FOREIGN KEY FK_EF39849B73A18A67');
        $this->addSql('ALTER TABLE deals DROP FOREIGN KEY FK_EF39849B78E2B382');
        $this->addSql('DROP INDEX IDX_EF39849B2AE63FDB ON deals');
        $this->addSql('DROP INDEX IDX_EF39849B73A18A67 ON deals');
        $this->addSql('DROP INDEX IDX_EF39849B78E2B382 ON deals');
        $this->addSql('ALTER TABLE deals DROP share_id, DROP bond_id, DROP future_id');
    }
}
