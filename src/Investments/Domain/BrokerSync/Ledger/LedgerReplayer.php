<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Ledger;

use App\Investments\Domain\BrokerSync\BrokerOperation;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\FeeAllocation;
use App\Investments\Domain\BrokerSync\InstrumentKind;
use App\Investments\Domain\Instruments\ShareSplit;

/**
 * Rebuilds lots, payouts, deposits and cash of an account by replaying its broker operations.
 * Replaying the whole journal every time keeps the result right when the broker revises the past.
 */
final readonly class LedgerReplayer
{
    /** A tax withheld this many days away from any payout is not attached to one. */
    private const int TAX_MATCH_DAYS = 31;

    public function __construct(
        private TradeFeeAllocator $feeAllocator = new TradeFeeAllocator(),
    ) {
    }

    /**
     * @param list<BrokerOperation> $operations executed operations in the order they happened
     * @param list<ShareSplit> $splits in the order of their trade dates
     */
    public function replay(array $operations, array $splits, FeeAllocation $feeAllocation, \DateTimeImmutable $asOf): Ledger
    {
        $fees = $this->feeAllocator->allocate($operations, $feeAllocation);
        $book = new LotBook();
        $warnings = [];
        $instruments = [];
        $cash = [];
        $cashFlows = [];
        $dividends = [];
        $coupons = [];
        $dividendTaxes = [];
        $couponTaxes = [];

        foreach ($operations as $operation) {
            $splits = $this->applySplits($book, $splits, Ledger::localDate($operation->getExecutedAt()), $warnings);
            $currency = $operation->getCurrency();
            $cash[$currency] = bcadd($cash[$currency] ?? '0', $operation->getPayment(), 9);

            $type = $operation->getType();
            if ($type === BrokerOperationType::Deposit || $type === BrokerOperationType::Withdrawal) {
                $cashFlows[] = new CashFlow($operation->getExternalId(), $operation->getExecutedAt(), $operation->getPayment(), $currency);
                continue;
            }

            $instrument = InstrumentRef::fromOperation($operation);
            if ($instrument === null) {
                continue;
            }

            switch ($type) {
                case BrokerOperationType::Dividend:
                    $dividends[] = new Payout($operation->getExternalId(), $instrument, $operation->getExecutedAt(), $operation->getPayment(), $currency);
                    $instruments[$instrument->uid] = $instrument;
                    continue 2;
                case BrokerOperationType::Coupon:
                    $coupons[] = new Payout($operation->getExternalId(), $instrument, $operation->getExecutedAt(), $operation->getPayment(), $currency);
                    $instruments[$instrument->uid] = $instrument;
                    continue 2;
                case BrokerOperationType::DividendTax:
                    $dividendTaxes[] = $operation;
                    continue 2;
                case BrokerOperationType::CouponTax:
                    $couponTaxes[] = $operation;
                    continue 2;
                default:
            }

            if (! $this->isPositionChange($type)) {
                continue;
            }
            if (! $instrument->isTradable()) {
                if ($instrument->kind !== null && $instrument->kind !== InstrumentKind::Currency) {
                    $warnings[] = sprintf('%s: %s trades are not turned into deals yet, they are counted only in cash', $instrument->ticker, $instrument->kind->value);
                }
                continue;
            }

            $instruments[$instrument->uid] = $instrument;
            $this->changePosition($book, $operation, $instrument, $fees[$operation->getExternalId()] ?? '0', $warnings);
        }

        $this->applySplits($book, $splits, Ledger::localDate($asOf), $warnings);
        $this->withholdTaxes($dividends, $dividendTaxes, $warnings);
        $this->withholdTaxes($coupons, $couponTaxes, $warnings);

        return new Ledger(
            lots:        $book->lots(),
            dividends:   $dividends,
            coupons:     $coupons,
            cashFlows:   $cashFlows,
            positions:   $book->positions(),
            instruments: $instruments,
            cash:        $cash,
            warnings:    array_values(array_unique($warnings)),
        );
    }

    private function isPositionChange(BrokerOperationType $type): bool
    {
        return in_array($type, [
            BrokerOperationType::Buy,
            BrokerOperationType::Sell,
            BrokerOperationType::SecuritiesIn,
            BrokerOperationType::SecuritiesOut,
            BrokerOperationType::BondRepayment,
            BrokerOperationType::BondMaturity,
        ], true);
    }

    /**
     * @param numeric-string $fee
     * @param list<string> $warnings
     */
    private function changePosition(LotBook $book, BrokerOperation $operation, InstrumentRef $instrument, string $fee, array &$warnings): void
    {
        $id = $operation->getExternalId();
        $at = $operation->getExecutedAt();
        $quantity = $operation->getQuantity();
        $currency = $operation->getCurrency();

        switch ($operation->getType()) {
            case BrokerOperationType::Buy:
            case BrokerOperationType::Sell:
                $price = $operation->getPrice();
                if ($quantity <= 0 || $price === null) {
                    $warnings[] = sprintf('%s: trade %s has no quantity or price and was skipped', $instrument->ticker, $id);

                    return;
                }
                if ($operation->getType() === BrokerOperationType::Buy) {
                    $book->buy($instrument, $id, $quantity, $price, $at, $fee, $currency);
                } else {
                    $book->sell($instrument, $id, $quantity, $price, $at, $fee, $currency);
                }

                return;
            case BrokerOperationType::SecuritiesIn:
                if ($operation->getPrice() === null) {
                    $warnings[] = sprintf('%s: securities came in without a purchase price, it is taken as zero', $instrument->ticker);
                }
                $book->buy($instrument, $id, $quantity, $operation->getPrice() ?? '0', $at, '0', $currency);

                return;
            case BrokerOperationType::SecuritiesOut:
                $book->withdraw($instrument->uid, $quantity, $at);

                return;
            case BrokerOperationType::BondRepayment:
                if (! $book->repay($instrument->uid, $operation->getPayment())) {
                    $warnings[] = sprintf('%s: a nominal repayment came without the bond in the account', $instrument->ticker);
                }

                return;
            case BrokerOperationType::BondMaturity:
                $quantity = $quantity > 0 ? $quantity : $book->openQuantity($instrument->uid);
                if ($quantity > 0) {
                    $price = bcdiv($operation->getPayment(), (string) $quantity, 9);
                    $book->sell($instrument, $id, $quantity, $price, $at, '0', $currency);
                }

                return;
            default:
        }
    }

    /**
     * Applies the splits that took effect by the given exchange date.
     *
     * @param list<ShareSplit> $splits
     * @param list<string> $warnings
     * @return list<ShareSplit> the splits still ahead
     */
    private function applySplits(LotBook $book, array $splits, \DateTimeImmutable $date, array &$warnings): array
    {
        while ($splits !== [] && $splits[0]->getTradeDate()->format('Y-m-d') <= $date->format('Y-m-d')) {
            $split = array_shift($splits);
            array_push($warnings, ...$book->applySplit($split->getTicker(), $split->getStockMarket(), $split->getBefore(), $split->getAfter()));
        }

        return $splits;
    }

    /**
     * Attaches every tax to the nearest payout of the same instrument.
     *
     * @param list<Payout> $payouts
     * @param list<BrokerOperation> $taxes
     * @param list<string> $warnings
     */
    private function withholdTaxes(array $payouts, array $taxes, array &$warnings): void
    {
        foreach ($taxes as $tax) {
            $nearest = null;
            $nearestDistance = self::TAX_MATCH_DAYS * 86400;
            foreach ($payouts as $payout) {
                if ($payout->getInstrument()->uid !== $tax->getInstrumentUid()) {
                    continue;
                }
                $distance = abs($payout->getPaidAt()->getTimestamp() - $tax->getExecutedAt()->getTimestamp());
                if ($distance <= $nearestDistance) {
                    $nearest = $payout;
                    $nearestDistance = $distance;
                }
            }

            if ($nearest === null) {
                $warnings[] = sprintf('%s: tax %s has no payout to belong to', $tax->getTicker() ?? (string) $tax->getInstrumentUid(), $tax->getExternalId());
                continue;
            }
            $nearest->withhold(bcmul($tax->getPayment(), '-1', 9));
        }
    }
}
