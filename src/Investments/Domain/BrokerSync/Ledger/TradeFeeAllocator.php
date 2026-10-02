<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Ledger;

use App\Investments\Domain\BrokerSync\BrokerOperation;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\FeeAllocation;

/**
 * Works out the commission of every trade, however the broker charges it:
 * per trade (a fee operation linked to the trade, or a commission inside the trade)
 * or as one sum for the day, which is spread over the day's trades by turnover.
 */
final class TradeFeeAllocator
{
    /** A daily fee charged after a day without trades goes to the last trading day within this many days. */
    private const int DAYS_TO_LOOK_BACK = 5;

    /**
     * @param list<BrokerOperation> $operations executed operations
     * @return array<string, numeric-string> commission by trade operation id; trades without one are absent
     */
    public function allocate(array $operations, FeeAllocation $allocation): array
    {
        $trades = [];
        foreach ($operations as $operation) {
            if ($this->isTrade($operation)) {
                $trades[$operation->getExternalId()] = $operation;
            }
        }

        $fees = [];
        /** @var array<string, numeric-string> $dailyFees by exchange date */
        $dailyFees = [];
        $linkedTrades = [];
        foreach ($operations as $operation) {
            if ($operation->getType() !== BrokerOperationType::TradeFee) {
                continue;
            }
            $amount = bcmul($operation->getPayment(), '-1', 9);
            $parentId = $operation->getParentExternalId();
            if ($allocation === FeeAllocation::PerOperation && $parentId !== null && isset($trades[$parentId])) {
                $fees[$parentId] = bcadd($fees[$parentId] ?? '0', $amount, 9);
                $linkedTrades[$parentId] = true;
                continue;
            }
            $day = $this->day($operation);
            $dailyFees[$day] = bcadd($dailyFees[$day] ?? '0', $amount, 9);
        }

        // A commission reported inside the trade counts unless fee operations already cover it
        foreach ($trades as $id => $trade) {
            $commission = $trade->getCommission();
            if ($commission === null || isset($linkedTrades[$id])) {
                continue;
            }
            if ($allocation === FeeAllocation::PerOperation) {
                $fees[$id] = bcadd($fees[$id] ?? '0', $commission, 9);
            } else {
                $day = $this->day($trade);
                $dailyFees[$day] = bcadd($dailyFees[$day] ?? '0', $commission, 9);
            }
        }

        foreach ($dailyFees as $day => $amount) {
            foreach ($this->spread($amount, $this->tradesOfDay($trades, $day)) as $id => $share) {
                $fees[$id] = bcadd($fees[$id] ?? '0', $share, 9);
            }
        }

        return $fees;
    }

    private function isTrade(BrokerOperation $operation): bool
    {
        if (! in_array($operation->getType(), [BrokerOperationType::Buy, BrokerOperationType::Sell], true)) {
            return false;
        }

        return InstrumentRef::fromOperation($operation)?->isTradable() ?? false;
    }

    /**
     * @param array<string, BrokerOperation> $trades
     * @return array<string, BrokerOperation>
     */
    private function tradesOfDay(array $trades, string $day): array
    {
        $date = new \DateTimeImmutable($day, new \DateTimeZone(Ledger::TIMEZONE));
        for ($i = 0; $i <= self::DAYS_TO_LOOK_BACK; $i++) {
            $candidate = $date->modify(sprintf('-%d days', $i))->format('Y-m-d');
            $found = array_filter($trades, fn (BrokerOperation $trade) => $this->day($trade) === $candidate);
            if ($found !== []) {
                return $found;
            }
        }

        return [];
    }

    /**
     * @param numeric-string $amount
     * @param array<string, BrokerOperation> $trades
     * @return array<string, numeric-string>
     */
    private function spread(string $amount, array $trades): array
    {
        $turnovers = [];
        $total = '0';
        foreach ($trades as $id => $trade) {
            $turnover = bcmul($trade->getPrice() ?? '0', (string) $trade->getQuantity(), 9);
            if (bccomp($turnover, '0', 9) <= 0) {
                $payment = $trade->getPayment();
                $turnover = bccomp($payment, '0', 9) < 0 ? bcmul($payment, '-1', 9) : $payment;
            }
            $turnovers[$id] = $turnover;
            $total = bcadd($total, $turnover, 9);
        }

        if (bccomp($total, '0', 9) <= 0) {
            return [];
        }

        $shares = [];
        $left = $amount;
        $ids = array_keys($turnovers);
        $lastId = end($ids);
        foreach ($turnovers as $id => $turnover) {
            $share = $id === $lastId ? $left : bcdiv(bcmul($amount, $turnover, 9), $total, 9);
            $shares[$id] = $share;
            $left = bcsub($left, $share, 9);
        }

        return $shares;
    }

    private function day(BrokerOperation $operation): string
    {
        return Ledger::localDate($operation->getExecutedAt())->format('Y-m-d');
    }
}
