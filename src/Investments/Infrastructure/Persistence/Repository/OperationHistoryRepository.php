<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\BrokerSync\BrokerOperationState;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Operations\History\OperationCategory;
use App\Investments\Domain\Operations\History\OperationHistoryEntry;
use App\Investments\Domain\Operations\History\OperationHistoryFilter;
use App\Investments\Domain\Operations\History\OperationHistoryRepositoryInterface;
use App\Investments\Domain\Operations\History\OperationHistorySource;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * One query over the journals of the manual accounts, their deposits and payouts, and the
 * operations of the synced accounts, so that the history pages through all of them at once.
 */
final readonly class OperationHistoryRepository implements OperationHistoryRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[\Override]
    public function count(int $userId, OperationHistoryFilter $filter): int
    {
        [$sql, $params, $types] = $this->union($userId, $filter);
        if ($sql === null) {
            return 0;
        }

        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM (' . $sql . ') h', $params, $types);
    }

    #[\Override]
    public function findPage(int $userId, OperationHistoryFilter $filter, int $offset, int $limit): array
    {
        [$sql, $params, $types] = $this->union($userId, $filter);
        if ($sql === null) {
            return [];
        }

        /** @var list<array<string, string|int|null>> $rows */
        $rows = $this->connection->fetchAllAssociative(
            sprintf('SELECT * FROM (%s) h ORDER BY h.executed_at DESC, h.source DESC, h.id DESC LIMIT %d OFFSET %d', $sql, $limit, $offset),
            $params,
            $types,
        );

        return array_map($this->entry(...), $rows);
    }

    /**
     * @return array{0: string|null, 1: array<string, mixed>, 2: array<string, ArrayParameterType::*>}
     */
    private function union(int $userId, OperationHistoryFilter $filter): array
    {
        $params = ['user' => $userId];
        $types = [];
        $accounts = 'a.user_id = :user';
        if ($filter->accountId !== null) {
            $accounts .= ' AND a.id = :account';
            $params['account'] = $filter->accountId;
        }
        // Deposits and payouts of a synced account are told by the broker operations
        $manual = $accounts . ' AND NOT EXISTS (SELECT 1 FROM broker_account_links ml WHERE ml.account_id = a.id)';
        $category = $filter->category;

        $branches = [];
        if ($category === null || $category->journalTypes() !== []) {
            // A block or a close keeps the lot, not always the security: take it from the purchase
            $branches[] = sprintf(
                "SELECT '%s' AS source, o.id, a.id AS account_id, a.name AS account_name, o.executed_at, o.type,
                        COALESCE(o.ticker, op.ticker) AS ticker, COALESCE(i.short_name, i.name) AS name, i.kind AS instrument_kind,
                        o.quantity, o.price, o.commission, o.amount, COALESCE(o.currency, i.currency) AS currency
                 FROM manual_operations o
                 JOIN accounts a ON a.id = o.account_id
                 LEFT JOIN manual_operations op ON op.account_id = o.account_id AND o.lot LIKE :lotPrefix
                     AND op.id = CAST(SUBSTRING_INDEX(SUBSTRING(o.lot, %d), '#', 1) AS UNSIGNED)
                 LEFT JOIN instruments i ON i.id = COALESCE(o.instrument_id, op.instrument_id)
                 WHERE %s AND a.journal_started_at IS NOT NULL%s",
                OperationHistorySource::Journal->value,
                strlen(ManualOperation::LOT_PREFIX) + 1,
                $accounts,
                $category !== null ? ' AND o.type IN (:journalTypes)' : '',
            );
            $params['lotPrefix'] = ManualOperation::LOT_PREFIX . '%';
            if ($category !== null) {
                $params['journalTypes'] = $category->journalTypes();
                $types['journalTypes'] = ArrayParameterType::STRING;
            }
        }
        if ($category === null || $category->brokerTypes() !== []) {
            $branches[] = sprintf(
                "SELECT '%s' AS source, b.id, a.id AS account_id, a.name AS account_name, b.executed_at, b.type,
                        b.ticker, b.name, b.instrument_kind, NULLIF(b.quantity, 0) AS quantity, b.price, b.commission,
                        b.payment AS amount, b.currency
                 FROM broker_operations b
                 JOIN broker_account_links l ON l.id = b.link_id
                 JOIN accounts a ON a.id = l.account_id
                 WHERE %s AND b.state = :executed%s",
                OperationHistorySource::Broker->value,
                $accounts,
                $category !== null ? ' AND b.type IN (:brokerTypes)' : '',
            );
            $params['executed'] = BrokerOperationState::Executed->value;
            if ($category !== null) {
                $params['brokerTypes'] = $category->brokerTypes();
                $types['brokerTypes'] = ArrayParameterType::STRING;
            }
        }
        if ($category === null || $category === OperationCategory::ofRecord(OperationHistorySource::Deposit)) {
            $branches[] = sprintf(
                "SELECT '%s' AS source, inv.id, a.id AS account_id, a.name AS account_name, CAST(inv.date AS DATETIME) AS executed_at,
                        IF(inv.sum < 0, 'withdrawal', 'deposit') AS type, NULL AS ticker, NULL AS name, NULL AS instrument_kind,
                        NULL AS quantity, NULL AS price, NULL AS commission, inv.sum AS amount, 'RUB' AS currency
                 FROM investments inv
                 JOIN accounts a ON a.id = inv.account_id
                 WHERE %s",
                OperationHistorySource::Deposit->value,
                $manual,
            );
        }
        if ($category === null || $category === OperationCategory::ofRecord(OperationHistorySource::Dividend)) {
            // A dividend is paid in the currency of its share, roubles when the catalogue does not know it
            $branches[] = sprintf(
                "SELECT '%s' AS source, d.id, a.id AS account_id, a.name AS account_name, CAST(d.date AS DATETIME) AS executed_at,
                        'dividend' AS type, COALESCE(s.ticker, d.ticker) AS ticker, COALESCE(s.short_name, s.name) AS name,
                        s.kind AS instrument_kind, NULL AS quantity, NULL AS price, NULL AS commission, d.amount,
                        COALESCE(s.currency, 'RUB') AS currency
                 FROM dividends d
                 JOIN accounts a ON a.id = d.account_id
                 LEFT JOIN instruments s ON s.id = d.share_id
                 WHERE %s",
                OperationHistorySource::Dividend->value,
                $manual,
            );
        }
        if ($category === null || $category === OperationCategory::ofRecord(OperationHistorySource::Coupon)) {
            $branches[] = sprintf(
                "SELECT '%s' AS source, c.id, a.id AS account_id, a.name AS account_name, CAST(c.date AS DATETIME) AS executed_at,
                        'coupon' AS type, COALESCE(bond.ticker, c.ticker) AS ticker, COALESCE(bond.short_name, bond.name) AS name,
                        bond.kind AS instrument_kind, NULL AS quantity, NULL AS price, NULL AS commission, c.amount,
                        'RUB' AS currency
                 FROM coupons c
                 JOIN accounts a ON a.id = c.account_id
                 LEFT JOIN instruments bond ON bond.id = c.bond_id
                 WHERE %s",
                OperationHistorySource::Coupon->value,
                $manual,
            );
        }

        return [$branches !== [] ? implode("\nUNION ALL\n", $branches) : null, $params, $types];
    }

    /**
     * @param array<string, string|int|null> $row
     */
    private function entry(array $row): OperationHistoryEntry
    {
        $number = static fn (string|int|null $value): ?string => $value !== null && is_numeric($value) ? bcadd((string) $value, '0', 4) : null;

        return new OperationHistoryEntry(
            source:         OperationHistorySource::from((string) $row['source']),
            id:             (int) $row['id'],
            accountId:      (int) $row['account_id'],
            accountName:    (string) $row['account_name'],
            executedAt:     new \DateTimeImmutable((string) $row['executed_at']),
            type:           (string) $row['type'],
            ticker:         $row['ticker'] !== null ? (string) $row['ticker'] : null,
            name:           $row['name'] !== null ? (string) $row['name'] : null,
            instrumentKind: $row['instrument_kind'] !== null ? (string) $row['instrument_kind'] : null,
            quantity:       $row['quantity'] !== null ? (int) $row['quantity'] : null,
            price:          $number($row['price']),
            commission:     $number($row['commission']),
            amount:         $number($row['amount']),
            currency:       $row['currency'] !== null ? (string) $row['currency'] : null,
        );
    }
}
