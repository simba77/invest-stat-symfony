<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tax column to dividends and backfill with 13%';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dividends ADD tax NUMERIC(18, 4) NOT NULL DEFAULT 0.0000');
        $this->addSql('UPDATE dividends SET tax = ROUND((amount * 13) / 87, 4)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dividends DROP tax');
    }
}
