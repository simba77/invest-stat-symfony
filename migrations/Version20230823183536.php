<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230823183536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // expenses_categories was renamed to expense_categories by hand on existing databases,
        // so an empty database has to replay the rename here.
        if (! $schema->hasTable('expense_categories') && $schema->hasTable('expenses_categories')) {
            $this->addSql('ALTER TABLE expenses DROP FOREIGN KEY FK_2496F35B12469DE2');
            $this->addSql('RENAME TABLE expenses_categories TO expense_categories');
        }

        $this->addSql('ALTER TABLE expenses ADD CONSTRAINT FK_2496F35B12469DE2 FOREIGN KEY (category_id) REFERENCES expense_categories (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expenses DROP FOREIGN KEY FK_2496F35B12469DE2');
    }
}
