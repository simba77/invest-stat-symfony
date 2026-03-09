<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tax profile to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD tax_profile VARCHAR(20) NOT NULL DEFAULT 'ndfl_13'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP tax_profile');
    }
}
