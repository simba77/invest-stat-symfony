<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250921100950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE future_multipliers (id INT AUTO_INCREMENT NOT NULL, ticker VARCHAR(255) NOT NULL, value NUMERIC(18, 4) NOT NULL, UNIQUE INDEX ticker (ticker), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rememberme_token CHANGE lastUsed lastUsed DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE future_multipliers');
        $this->addSql('ALTER TABLE rememberme_token CHANGE lastUsed lastUsed DATETIME NOT NULL');
    }
}
