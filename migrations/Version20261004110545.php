<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004110545 extends AbstractMigration
{
    private const array LEGACY_TABLES = ['share' => 'shares', 'bond' => 'bonds', 'future' => 'futures'];

    public function getDescription(): string
    {
        return 'Keep shares, bonds and futures in one instruments catalogue that deals, dividends and coupons refer to';
    }

    public function up(Schema $schema): void
    {
        // A ticker becomes unique on its exchange: deals of a duplicate move to the first copy
        foreach (self::LEGACY_TABLES as $kind => $table) {
            $this->addSql(sprintf(
                'UPDATE deals d JOIN (SELECT dup.id AS dup_id, (SELECT MIN(k.id) FROM %1$s k WHERE k.ticker = dup.ticker AND k.stock_market = dup.stock_market) AS keep_id FROM %1$s dup) m
                 ON m.dup_id = d.%2$s_id SET d.%2$s_id = m.keep_id WHERE m.keep_id <> m.dup_id',
                $table,
                $kind,
            ));
            $this->addSql(sprintf(
                'DELETE dup FROM %1$s dup JOIN %1$s keep ON keep.ticker = dup.ticker AND keep.stock_market = dup.stock_market AND keep.id < dup.id',
                $table,
            ));
        }

        $this->addSql('CREATE TABLE instruments (id INT AUTO_INCREMENT NOT NULL, ticker VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, short_name VARCHAR(255) DEFAULT NULL, lat_name VARCHAR(255) DEFAULT NULL, stock_market VARCHAR(16) NOT NULL, currency VARCHAR(8) NOT NULL, price NUMERIC(18, 4) NOT NULL, prev_price NUMERIC(18, 4) DEFAULT NULL, lot_size NUMERIC(18, 4) DEFAULT NULL, isin VARCHAR(32) DEFAULT NULL, class_code VARCHAR(32) DEFAULT NULL, t_uid VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', kind VARCHAR(16) NOT NULL, share_type SMALLINT DEFAULT NULL, sector VARCHAR(255) DEFAULT NULL, price_step NUMERIC(18, 4) DEFAULT NULL, coupon_percent NUMERIC(18, 4) DEFAULT NULL, coupon_value NUMERIC(18, 4) DEFAULT NULL, coupon_accumulated NUMERIC(18, 4) DEFAULT NULL, next_coupon_date DATE DEFAULT NULL, maturity_date DATE DEFAULT NULL, expiration DATE DEFAULT NULL, step_price NUMERIC(18, 4) DEFAULT NULL, multiplier NUMERIC(18, 4) DEFAULT NULL, legacy_id INT DEFAULT NULL, INDEX instrument_ticker (ticker), INDEX instrument_t_uid (t_uid), UNIQUE INDEX instrument_market_ticker (stock_market, ticker), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO instruments (kind, legacy_id, ticker, name, short_name, lat_name, stock_market, currency, price, prev_price, lot_size, isin, class_code, t_uid, created_at, updated_at, share_type, sector)
            SELECT \'share\', id, ticker, name, short_name, lat_name, stock_market, currency, price, prev_price, lot_size, isin, class_code, t_uid, created_at, updated_at, type, sector FROM shares ORDER BY id');
        $this->addSql('INSERT INTO instruments (kind, legacy_id, ticker, name, short_name, lat_name, stock_market, currency, price, prev_price, lot_size, t_uid, created_at, updated_at, price_step, coupon_percent, coupon_value, coupon_accumulated, next_coupon_date, maturity_date)
            SELECT \'bond\', id, ticker, name, short_name, lat_name, stock_market, currency, price, prev_price, lot_size, t_uid, created_at, updated_at, step_price, coupon_percent, coupon_value, coupon_accumulated, next_coupon_date, maturity_date FROM bonds ORDER BY id');
        $this->addSql('INSERT INTO instruments (kind, legacy_id, ticker, name, short_name, lat_name, stock_market, currency, price, prev_price, lot_size, t_uid, created_at, updated_at, expiration, step_price, multiplier)
            SELECT \'future\', f.id, f.ticker, f.name, f.short_name, f.lat_name, f.stock_market, f.currency, f.price, f.prev_price, f.lot_size, f.t_uid, f.created_at, f.updated_at, f.expiration, f.step_price, m.value
            FROM futures f LEFT JOIN future_multipliers m ON m.ticker = f.ticker ORDER BY f.id');

        $this->addSql('ALTER TABLE deals DROP FOREIGN KEY FK_EF39849B2AE63FDB');
        $this->addSql('ALTER TABLE deals DROP FOREIGN KEY FK_EF39849B73A18A67');
        $this->addSql('ALTER TABLE deals DROP FOREIGN KEY FK_EF39849B78E2B382');
        $this->addSql('DROP INDEX IDX_EF39849B2AE63FDB ON deals');
        $this->addSql('DROP INDEX IDX_EF39849B73A18A67 ON deals');
        $this->addSql('DROP INDEX IDX_EF39849B78E2B382 ON deals');
        $this->addSql('ALTER TABLE deals ADD instrument_id INT DEFAULT NULL');
        foreach (array_keys(self::LEGACY_TABLES) as $kind) {
            $this->addSql(sprintf(
                'UPDATE deals d JOIN instruments i ON i.kind = \'%1$s\' AND i.legacy_id = d.%1$s_id SET d.instrument_id = i.id',
                $kind,
            ));
        }
        $this->addSql('ALTER TABLE deals DROP share_id, DROP bond_id, DROP future_id');
        $this->addSql('ALTER TABLE deals ADD CONSTRAINT FK_EF39849BCF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id)');
        $this->addSql('CREATE INDEX IDX_EF39849BCF11D9C ON deals (instrument_id)');

        // Payouts knew their security by ticker only
        $this->addSql('ALTER TABLE dividends ADD share_id INT DEFAULT NULL');
        $this->addSql('UPDATE dividends d SET d.share_id = (SELECT MIN(i.id) FROM instruments i WHERE i.kind = \'share\' AND i.ticker = d.ticker AND i.stock_market = d.stock_market)');
        $this->addSql('ALTER TABLE dividends ADD CONSTRAINT FK_62FF7AA62AE63FDB FOREIGN KEY (share_id) REFERENCES instruments (id)');
        $this->addSql('CREATE INDEX IDX_62FF7AA62AE63FDB ON dividends (share_id)');
        $this->addSql('ALTER TABLE coupons ADD bond_id INT DEFAULT NULL');
        $this->addSql('UPDATE coupons c SET c.bond_id = (SELECT MIN(i.id) FROM instruments i WHERE i.kind = \'bond\' AND i.ticker = c.ticker AND i.stock_market = c.stock_market)');
        $this->addSql('ALTER TABLE coupons ADD CONSTRAINT FK_F564111873A18A67 FOREIGN KEY (bond_id) REFERENCES instruments (id)');
        $this->addSql('CREATE INDEX IDX_F564111873A18A67 ON coupons (bond_id)');

        $this->addSql('DROP TABLE shares');
        $this->addSql('DROP TABLE bonds');
        $this->addSql('DROP TABLE futures');
        $this->addSql('DROP TABLE future_multipliers');
        $this->addSql('ALTER TABLE instruments DROP legacy_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shares (id INT AUTO_INCREMENT NOT NULL, ticker VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, short_name VARCHAR(255) DEFAULT NULL, lat_name VARCHAR(255) DEFAULT NULL, stock_market VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, price NUMERIC(18, 4) NOT NULL, lot_size NUMERIC(18, 4) DEFAULT NULL, isin VARCHAR(255) DEFAULT NULL, type SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', prev_price NUMERIC(18, 4) DEFAULT NULL, class_code VARCHAR(255) DEFAULT NULL, sector VARCHAR(255) DEFAULT NULL, t_uid VARCHAR(255) DEFAULT NULL, INDEX ticker_market (ticker, stock_market), INDEX t_uid (t_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bonds (id INT AUTO_INCREMENT NOT NULL, ticker VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, short_name VARCHAR(255) DEFAULT NULL, lat_name VARCHAR(255) DEFAULT NULL, stock_market VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, lot_size NUMERIC(18, 4) DEFAULT NULL, price NUMERIC(18, 4) NOT NULL, step_price NUMERIC(18, 4) DEFAULT NULL, coupon_percent NUMERIC(18, 4) DEFAULT NULL, coupon_value NUMERIC(18, 4) DEFAULT NULL, coupon_accumulated NUMERIC(18, 4) DEFAULT NULL, next_coupon_date DATE DEFAULT NULL, maturity_date DATE DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', prev_price NUMERIC(18, 4) DEFAULT NULL, t_uid CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', INDEX ticker_market (ticker, stock_market), INDEX t_uid (t_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE futures (id INT AUTO_INCREMENT NOT NULL, ticker VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, short_name VARCHAR(255) DEFAULT NULL, lat_name VARCHAR(255) DEFAULT NULL, stock_market VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, price NUMERIC(18, 4) NOT NULL, lot_size NUMERIC(18, 4) DEFAULT NULL, expiration DATE DEFAULT NULL, step_price NUMERIC(18, 4) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', prev_price NUMERIC(18, 4) DEFAULT NULL, t_uid CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', INDEX ticker_market (ticker, stock_market), INDEX t_uid (t_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE future_multipliers (id INT AUTO_INCREMENT NOT NULL, ticker VARCHAR(255) NOT NULL, value NUMERIC(18, 4) NOT NULL, UNIQUE INDEX ticker (ticker), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO shares (id, ticker, name, short_name, lat_name, stock_market, currency, price, lot_size, isin, type, created_at, updated_at, prev_price, class_code, sector, t_uid)
            SELECT id, ticker, name, short_name, lat_name, stock_market, currency, price, lot_size, isin, COALESCE(share_type, 1), created_at, updated_at, prev_price, class_code, sector, t_uid FROM instruments WHERE kind = \'share\'');
        $this->addSql('INSERT INTO bonds (id, ticker, name, short_name, lat_name, stock_market, currency, lot_size, price, step_price, coupon_percent, coupon_value, coupon_accumulated, next_coupon_date, maturity_date, created_at, updated_at, prev_price, t_uid)
            SELECT id, ticker, name, short_name, lat_name, stock_market, currency, lot_size, price, price_step, coupon_percent, coupon_value, coupon_accumulated, next_coupon_date, maturity_date, created_at, updated_at, prev_price, t_uid FROM instruments WHERE kind = \'bond\'');
        $this->addSql('INSERT INTO futures (id, ticker, name, short_name, lat_name, stock_market, currency, price, lot_size, expiration, step_price, created_at, updated_at, prev_price, t_uid)
            SELECT id, ticker, name, short_name, lat_name, stock_market, currency, price, lot_size, expiration, step_price, created_at, updated_at, prev_price, t_uid FROM instruments WHERE kind = \'future\'');
        $this->addSql('INSERT INTO future_multipliers (ticker, value) SELECT ticker, multiplier FROM instruments WHERE kind = \'future\' AND multiplier IS NOT NULL');

        $this->addSql('ALTER TABLE deals ADD share_id INT DEFAULT NULL, ADD bond_id INT DEFAULT NULL, ADD future_id INT DEFAULT NULL');
        foreach (array_keys(self::LEGACY_TABLES) as $kind) {
            $this->addSql(sprintf(
                'UPDATE deals d JOIN instruments i ON i.id = d.instrument_id AND i.kind = \'%1$s\' SET d.%1$s_id = i.id',
                $kind,
            ));
        }
        $this->addSql('ALTER TABLE deals DROP FOREIGN KEY FK_EF39849BCF11D9C');
        $this->addSql('DROP INDEX IDX_EF39849BCF11D9C ON deals');
        $this->addSql('ALTER TABLE deals DROP instrument_id');
        $this->addSql('ALTER TABLE deals ADD CONSTRAINT FK_EF39849B2AE63FDB FOREIGN KEY (share_id) REFERENCES shares (id)');
        $this->addSql('ALTER TABLE deals ADD CONSTRAINT FK_EF39849B73A18A67 FOREIGN KEY (bond_id) REFERENCES bonds (id)');
        $this->addSql('ALTER TABLE deals ADD CONSTRAINT FK_EF39849B78E2B382 FOREIGN KEY (future_id) REFERENCES futures (id)');
        $this->addSql('CREATE INDEX IDX_EF39849B2AE63FDB ON deals (share_id)');
        $this->addSql('CREATE INDEX IDX_EF39849B73A18A67 ON deals (bond_id)');
        $this->addSql('CREATE INDEX IDX_EF39849B78E2B382 ON deals (future_id)');

        $this->addSql('ALTER TABLE dividends DROP FOREIGN KEY FK_62FF7AA62AE63FDB');
        $this->addSql('DROP INDEX IDX_62FF7AA62AE63FDB ON dividends');
        $this->addSql('ALTER TABLE dividends DROP share_id');
        $this->addSql('ALTER TABLE coupons DROP FOREIGN KEY FK_F564111873A18A67');
        $this->addSql('DROP INDEX IDX_F564111873A18A67 ON coupons');
        $this->addSql('ALTER TABLE coupons DROP bond_id');
        $this->addSql('DROP TABLE instruments');
    }
}
