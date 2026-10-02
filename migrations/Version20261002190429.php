<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002190429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Share splits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE share_splits (id INT AUTO_INCREMENT NOT NULL, ticker VARCHAR(64) NOT NULL, stock_market VARCHAR(16) NOT NULL, trade_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', shares_before INT NOT NULL, shares_after INT NOT NULL, UNIQUE INDEX share_split_ticker_date (ticker, stock_market, trade_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE share_splits');
    }
}
