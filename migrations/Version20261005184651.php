<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005184651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Close accounts; statistics go with a deleted account';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts ADD closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE statistic DROP FOREIGN KEY FK_649B469C9B6B5FBA');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT FK_649B469C9B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE statistic DROP FOREIGN KEY FK_649B469C9B6B5FBA');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT FK_649B469C9B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id)');
        $this->addSql('ALTER TABLE accounts DROP closed_at');
    }
}
