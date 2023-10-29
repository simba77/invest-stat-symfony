<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231021093253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE deposit_accounts (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, INDEX IDX_3670DC2AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE deposits (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, deposit_account_id INT NOT NULL, sum NUMERIC(18, 2) NOT NULL, type INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, INDEX IDX_449E9C9EA76ED395 (user_id), INDEX IDX_449E9C9E6E60BC73 (deposit_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE deposit_accounts ADD CONSTRAINT FK_3670DC2AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE deposits ADD CONSTRAINT FK_449E9C9EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE deposits ADD CONSTRAINT FK_449E9C9E6E60BC73 FOREIGN KEY (deposit_account_id) REFERENCES deposit_accounts (id)');
        $this->addSql('ALTER TABLE users CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deposit_accounts DROP FOREIGN KEY FK_3670DC2AA76ED395');
        $this->addSql('ALTER TABLE deposits DROP FOREIGN KEY FK_449E9C9EA76ED395');
        $this->addSql('ALTER TABLE deposits DROP FOREIGN KEY FK_449E9C9E6E60BC73');
        $this->addSql('DROP TABLE deposit_accounts');
        $this->addSql('DROP TABLE deposits');
        $this->addSql('ALTER TABLE users CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
