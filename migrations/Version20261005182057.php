<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005182057 extends AbstractMigration
{
    private const array RECORDS = ['deals', 'dividends', 'coupons', 'investments'];

    public function getDescription(): string
    {
        return 'Tie deals, payouts and deposits to their owner through their account';
    }

    public function up(Schema $schema): void
    {
        foreach (self::RECORDS as $table) {
            $mismatches = (int) $this->connection->fetchOne(sprintf(
                'SELECT COUNT(*) FROM %s r JOIN accounts a ON a.id = r.account_id WHERE r.user_id <> a.user_id',
                $table,
            ));
            $this->abortIf($mismatches > 0, sprintf('%d %s belong to another user than their account', $mismatches, $table));
        }
        $this->abortIf((int) $this->connection->fetchOne('SELECT COUNT(*) FROM investments WHERE account_id IS NULL') > 0, 'Deposits without an account have no owner left');

        $this->addSql('ALTER TABLE coupons DROP FOREIGN KEY FK_F5641118A76ED395');
        $this->addSql('DROP INDEX IDX_F5641118A76ED395 ON coupons');
        $this->addSql('ALTER TABLE coupons DROP user_id');
        $this->addSql('ALTER TABLE deals DROP FOREIGN KEY FK_EF39849BA76ED395');
        $this->addSql('DROP INDEX user_status ON deals');
        $this->addSql('DROP INDEX IDX_EF39849BA76ED395 ON deals');
        $this->addSql('ALTER TABLE deals DROP user_id');
        $this->addSql('CREATE INDEX deal_account_status ON deals (account_id, status)');
        $this->addSql('ALTER TABLE dividends DROP FOREIGN KEY FK_62FF7AA6A76ED395');
        $this->addSql('DROP INDEX IDX_62FF7AA6A76ED395 ON dividends');
        $this->addSql('ALTER TABLE dividends DROP user_id');
        $this->addSql('ALTER TABLE investments DROP user_id, CHANGE account_id account_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE investments ADD user_id INT DEFAULT NULL, CHANGE account_id account_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE dividends ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deals ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE coupons ADD user_id INT DEFAULT NULL');
        foreach (self::RECORDS as $table) {
            $this->addSql(sprintf('UPDATE %s r JOIN accounts a ON a.id = r.account_id SET r.user_id = a.user_id', $table));
            $this->addSql(sprintf('ALTER TABLE %s CHANGE user_id user_id INT NOT NULL', $table));
        }
        $this->addSql('DROP INDEX deal_account_status ON deals');
        $this->addSql('ALTER TABLE deals ADD CONSTRAINT FK_EF39849BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_EF39849BA76ED395 ON deals (user_id)');
        $this->addSql('CREATE INDEX user_status ON deals (user_id, status)');
        $this->addSql('ALTER TABLE dividends ADD CONSTRAINT FK_62FF7AA6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_62FF7AA6A76ED395 ON dividends (user_id)');
        $this->addSql('ALTER TABLE coupons ADD CONSTRAINT FK_F5641118A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_F5641118A76ED395 ON coupons (user_id)');
    }
}
