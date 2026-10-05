<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005184225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link accounts to their owners';
    }

    public function up(Schema $schema): void
    {
        $orphans = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM accounts a LEFT JOIN users u ON u.id = a.user_id WHERE u.id IS NULL');
        $this->abortIf($orphans > 0, sprintf('%d accounts belong to no existing user', $orphans));

        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT FK_CAC89EACA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_CAC89EACA76ED395 ON accounts (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY FK_CAC89EACA76ED395');
        $this->addSql('DROP INDEX IDX_CAC89EACA76ED395 ON accounts');
    }
}
