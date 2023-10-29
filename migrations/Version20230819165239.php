<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230819165239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accounts ADD balance NUMERIC(18, 4) DEFAULT NULL, ADD usd_balance NUMERIC(18, 4) DEFAULT NULL, ADD start_sum_of_assets NUMERIC(18, 4) DEFAULT NULL, ADD current_sum_of_assets NUMERIC(18, 4) DEFAULT NULL, ADD commission NUMERIC(18, 2) DEFAULT NULL, ADD futures_commission NUMERIC(18, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accounts DROP balance, DROP usd_balance, DROP start_sum_of_assets, DROP current_sum_of_assets, DROP commission, DROP futures_commission');
    }
}
