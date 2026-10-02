<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002191335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mark records rebuilt by the broker sync and keep actual deal commissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coupons ADD source SMALLINT DEFAULT 1 NOT NULL, ADD external_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX coupon_external_id ON coupons (account_id, external_id)');
        $this->addSql('ALTER TABLE deals ADD buy_commission NUMERIC(18, 4) DEFAULT NULL, ADD sell_commission NUMERIC(18, 4) DEFAULT NULL, ADD source SMALLINT DEFAULT 1 NOT NULL, ADD external_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX deal_external_id ON deals (account_id, external_id)');
        $this->addSql('ALTER TABLE dividends ADD source SMALLINT DEFAULT 1 NOT NULL, ADD external_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX dividend_external_id ON dividends (account_id, external_id)');
        $this->addSql('ALTER TABLE investments ADD source SMALLINT DEFAULT 1 NOT NULL, ADD external_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX investment_external_id ON investments (account_id, external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX coupon_external_id ON coupons');
        $this->addSql('ALTER TABLE coupons DROP source, DROP external_id');
        $this->addSql('DROP INDEX deal_external_id ON deals');
        $this->addSql('ALTER TABLE deals DROP buy_commission, DROP sell_commission, DROP source, DROP external_id');
        $this->addSql('DROP INDEX dividend_external_id ON dividends');
        $this->addSql('ALTER TABLE dividends DROP source, DROP external_id');
        $this->addSql('DROP INDEX investment_external_id ON investments');
        $this->addSql('ALTER TABLE investments DROP source, DROP external_id');
    }
}
