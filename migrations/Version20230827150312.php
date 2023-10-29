<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230827150312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE deal (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, account_id INT NOT NULL, ticker VARCHAR(255) NOT NULL, stock_market VARCHAR(255) NOT NULL, quantity INT NOT NULL, buy_price NUMERIC(18, 4) NOT NULL, target_price NUMERIC(18, 4) DEFAULT NULL, sell_price NUMERIC(18, 4) DEFAULT NULL, status SMALLINT NOT NULL, type SMALLINT NOT NULL, INDEX IDX_E3FEC116A76ED395 (user_id), INDEX IDX_E3FEC1169B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE deal ADD CONSTRAINT FK_E3FEC116A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE deal ADD CONSTRAINT FK_E3FEC1169B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deal DROP FOREIGN KEY FK_E3FEC116A76ED395');
        $this->addSql('ALTER TABLE deal DROP FOREIGN KEY FK_E3FEC1169B6B5FBA');
        $this->addSql('DROP TABLE deal');
    }
}
