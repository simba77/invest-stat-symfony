<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004112512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep the money of accounts by currency and stop keeping their computed value';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE account_cash (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, currency VARCHAR(8) NOT NULL, amount NUMERIC(18, 4) NOT NULL, INDEX IDX_8A2A62139B6B5FBA (account_id), UNIQUE INDEX account_cash_currency (account_id, currency), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE account_cash ADD CONSTRAINT FK_8A2A62139B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO account_cash (account_id, currency, amount) SELECT id, \'RUB\', COALESCE(balance, 0) FROM accounts');
        $this->addSql('INSERT INTO account_cash (account_id, currency, amount) SELECT id, \'USD\', COALESCE(usd_balance, 0) FROM accounts');
        $this->addSql('ALTER TABLE accounts DROP balance, DROP usd_balance, DROP start_sum_of_assets, DROP current_sum_of_assets');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts ADD balance NUMERIC(18, 4) DEFAULT NULL, ADD usd_balance NUMERIC(18, 4) DEFAULT NULL, ADD start_sum_of_assets NUMERIC(18, 4) DEFAULT NULL, ADD current_sum_of_assets NUMERIC(18, 4) DEFAULT NULL');
        $this->addSql('UPDATE accounts a SET a.balance = (SELECT c.amount FROM account_cash c WHERE c.account_id = a.id AND c.currency = \'RUB\'), a.usd_balance = (SELECT c.amount FROM account_cash c WHERE c.account_id = a.id AND c.currency = \'USD\')');
        $this->addSql('DROP TABLE account_cash');
    }
}
