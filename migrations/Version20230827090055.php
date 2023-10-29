<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230827090055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bonds (id INT AUTO_INCREMENT NOT NULL, ticker VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, short_name VARCHAR(255) DEFAULT NULL, lat_name VARCHAR(255) DEFAULT NULL, stock_market VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, lot_size NUMERIC(18, 4) DEFAULT NULL, price NUMERIC(18, 4) NOT NULL, step_price NUMERIC(18, 4) DEFAULT NULL, coupon_percent NUMERIC(18, 4) DEFAULT NULL, coupon_value NUMERIC(18, 4) DEFAULT NULL, coupon_accumulated NUMERIC(18, 4) DEFAULT NULL, next_coupon_date DATE DEFAULT NULL, maturity_date DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE bonds');
    }
}
