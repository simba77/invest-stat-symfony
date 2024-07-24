<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240724180246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dividend (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, account_id INT NOT NULL, ticker VARCHAR(255) NOT NULL, stock_market VARCHAR(255) NOT NULL, amount NUMERIC(18, 4) NOT NULL, date DATE NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, INDEX IDX_2D0D0909A76ED395 (user_id), INDEX IDX_2D0D09099B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dividend ADD CONSTRAINT FK_2D0D0909A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE dividend ADD CONSTRAINT FK_2D0D09099B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dividend DROP FOREIGN KEY FK_2D0D0909A76ED395');
        $this->addSql('ALTER TABLE dividend DROP FOREIGN KEY FK_2D0D09099B6B5FBA');
        $this->addSql('DROP TABLE dividend');
    }
}
