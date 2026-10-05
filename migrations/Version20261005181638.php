<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005181638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep the blocked part of the cash of accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account_cash ADD blocked NUMERIC(18, 4) DEFAULT \'0\' NOT NULL');
        // The dashboard counted the dollars of account 1 as blocked; that becomes a block of cash in its journal
        $this->addSql('INSERT INTO manual_operations (account_id, type, executed_at, amount, currency, created_at)
            SELECT c.account_id, \'block_cash\', NOW(), c.amount, c.currency, NOW()
            FROM account_cash c JOIN accounts a ON a.id = c.account_id
            WHERE c.account_id = 1 AND c.currency = \'USD\' AND c.amount <> 0 AND a.journal_started_at IS NOT NULL');
        $this->addSql('UPDATE account_cash SET blocked = amount WHERE account_id = 1 AND currency = \'USD\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM manual_operations WHERE type = \'block_cash\'');
        $this->addSql('ALTER TABLE account_cash DROP blocked');
    }
}
