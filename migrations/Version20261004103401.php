<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004103401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep a currency rate per exchange day';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE currency_rates ADD date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        // The rates kept so far are the current ones, last set when they were updated
        $this->addSql('UPDATE currency_rates SET date = DATE(COALESCE(updated_at, created_at))');
        $this->addSql('ALTER TABLE currency_rates CHANGE date date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX currency_rate_day ON currency_rates (base_currency, target_currency, date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX currency_rate_day ON currency_rates');
        // Only the latest day of every pair can stay without the date column
        $this->addSql('DELETE r FROM currency_rates r JOIN currency_rates newer ON newer.base_currency = r.base_currency AND newer.target_currency = r.target_currency AND newer.date > r.date');
        $this->addSql('ALTER TABLE currency_rates DROP date');
    }
}
