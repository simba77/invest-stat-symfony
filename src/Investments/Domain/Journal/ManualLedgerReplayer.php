<?php

declare(strict_types=1);

namespace App\Investments\Domain\Journal;

use App\Investments\Domain\BrokerSync\InstrumentKind;
use App\Investments\Domain\BrokerSync\Ledger\InstrumentRef;
use App\Investments\Domain\BrokerSync\Ledger\Lot;
use App\Investments\Domain\BrokerSync\Ledger\LotBook;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Instrument;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\Deals\DealType;

/**
 * Rebuilds the lots and the trading cash of a manual account from its journal.
 *
 * A purchase pays for the securities and a sale brings their price: a bond is paid for by its
 * nominal times the price in percent plus the accrued coupon, a future moves no money until it
 * is closed and then brings its result. Cash follows the currency of the security.
 */
final readonly class ManualLedgerReplayer
{
    /**
     * @param list<ManualOperation> $operations in the order they happened
     */
    public function replay(array $operations): ManualLedger
    {
        $state = new ManualLedgerState();
        foreach ($operations as $operation) {
            $this->apply($state, $operation);
        }
        foreach ($state->pending as $lot => $waiting) {
            $state->warnings[] = sprintf('%d operations refer to lot %s that was never opened', count($waiting), $lot);
        }

        return new ManualLedger($state->book->lots(), $state->instruments, $state->cash, $state->blockedCash, $state->knownCommissions, $state->warnings);
    }

    private function apply(ManualLedgerState $state, ManualOperation $operation): void
    {
        $type = $operation->getType();
        if ($type === ManualOperationType::CashAdjustment) {
            $this->addCash($state, (string) $operation->getCurrency(), $operation->getAmount());

            return;
        }
        if ($type === ManualOperationType::BlockCash) {
            $currency = (string) $operation->getCurrency();
            $state->blockedCash[$currency] = bcadd($state->blockedCash[$currency] ?? '0', $operation->getAmount(), 4);

            return;
        }

        // A close or a block may come before the purchase it refers to, when the dates were entered so
        $lot = $operation->getLot();
        if ($lot !== null && ! isset($state->opened[self::origin($lot)])) {
            $state->pending[self::origin($lot)][] = $operation;

            return;
        }

        if ($type === ManualOperationType::Close) {
            $this->close($state, $operation);
        } elseif ($type === ManualOperationType::Block || $type === ManualOperationType::Unblock) {
            // A lot closed meanwhile stays as it was
            $state->book->findOpen((string) $lot)?->setBlocked($type === ManualOperationType::Block);
        } else {
            $this->open($state, $operation);
        }
    }

    private function open(ManualLedgerState $state, ManualOperation $operation): void
    {
        $direction = $operation->getType()->direction() ?? DealType::Long;
        $instrument = $this->instrument($state, $operation);
        $key = $operation->getOpenedLot();
        $lot = $state->book->open(
            instrument: $instrument,
            key:        $key,
            direction:  $direction,
            quantity:   $operation->getQuantity(),
            price:      $operation->getPrice(),
            at:         $operation->getExecutedAt(),
            commission: $operation->getCommission() ?? '0',
            currency:   $this->currency($state, $instrument),
            target:     $operation->getTargetPrice(),
        );
        if ($operation->getCommission() !== null) {
            $state->knownCommissions[$key] = true;
        }

        $money = $this->money($state, $lot, $operation->getPrice(), $operation->getQuantity(), $operation->getAccruedInterest());
        $this->addCash($state, $lot->getCurrency(), $direction === DealType::Long ? bcmul($money, '-1', 4) : $money);
        $this->addCash($state, $lot->getCurrency(), bcmul($operation->getCommission() ?? '0', '-1', 4));
        $state->opened[$key] = true;

        foreach ($state->pending[$key] ?? [] as $waiting) {
            $this->apply($state, $waiting);
        }
        unset($state->pending[$key]);
    }

    private function close(ManualLedgerState $state, ManualOperation $operation): void
    {
        $instrument = $this->instrument($state, $operation);
        $parts = $state->book->close(
            uid:        $instrument->uid,
            key:        $operation->getLot(),
            quantity:   $operation->getQuantity(),
            price:      $operation->getPrice(),
            at:         $operation->getExecutedAt(),
            commission: $operation->getCommission() ?? '0',
        );

        $closed = 0;
        foreach ($parts as $part) {
            $closed += $part->getQuantity();
            $this->addCash($state, $part->getCurrency(), $this->result($state, $part, $operation));
        }
        if ($parts !== []) {
            $this->addCash($state, $parts[0]->getCurrency(), bcmul($operation->getCommission() ?? '0', '-1', 4));
        }
        if ($closed < $operation->getQuantity()) {
            $state->warnings[] = sprintf('%s: %d of %d securities were not open to be closed', $instrument->ticker, $operation->getQuantity() - $closed, $operation->getQuantity());
        }
    }

    /**
     * What closing a part of a lot brings: the sale price for a long lot, the buy-back price for
     * a short one, the price difference for a future.
     *
     * @return numeric-string
     */
    private function result(ManualLedgerState $state, Lot $part, ManualOperation $operation): string
    {
        $instrument = $state->instruments[$part->getInstrument()->uid] ?? null;
        $sign = $part->getDirection() === DealType::Long ? '1' : '-1';
        if ($instrument instanceof Future) {
            $points = bcsub((string) $part->getClosePrice(), $part->getOpenPrice(), 9);

            return bcmul(bcmul(bcmul($points, $this->pointValue($instrument), 9), (string) $part->getQuantity(), 9), $sign, 4);
        }

        return bcmul($this->money($state, $part, (string) $part->getClosePrice(), $part->getQuantity(), $operation->getAccruedInterest()), $sign, 4);
    }

    /**
     * The money of a trade of the lot's security; a future moves none when opened.
     *
     * @param numeric-string $price as the security is quoted
     * @param numeric-string|null $accruedInterest per security
     * @return numeric-string
     */
    private function money(ManualLedgerState $state, Lot $lot, string $price, int $quantity, ?string $accruedInterest): string
    {
        $instrument = $state->instruments[$lot->getInstrument()->uid] ?? null;
        if ($instrument instanceof Future) {
            return '0';
        }

        $perSecurity = $price;
        $nominal = $instrument instanceof Bond ? $instrument->getLotSize() : null;
        if ($nominal !== null) {
            $perSecurity = bcadd(bcdiv(bcmul($price, $nominal, 9), '100', 9), $accruedInterest ?? '0', 9);
        }

        return bcmul($perSecurity, (string) $quantity, 4);
    }

    /**
     * @return numeric-string roubles per point of one contract
     */
    private function pointValue(Future $future): string
    {
        return bcmul($future->getPointValue(), $future->getLotSize() ?? '1', 9);
    }

    private function instrument(ManualLedgerState $state, ManualOperation $operation): InstrumentRef
    {
        $instrument = $operation->getInstrument();
        $ticker = $operation->getTicker() ?? $instrument?->getTicker() ?? '';
        $stockMarket = $operation->getStockMarket() ?? $instrument?->getStockMarket() ?? 'MOEX';
        $uid = $instrument !== null ? 'instrument:' . (string) $instrument->getId() : sprintf('ticker:%s:%s', $stockMarket, $ticker);
        $state->instruments[$uid] = $instrument;

        // Deals keep the ticker as it was entered
        return new InstrumentRef(
            uid:         $uid,
            ticker:      $ticker,
            stockMarket: $stockMarket,
            classCode:   null,
            kind:        self::kind($instrument),
            name:        $instrument?->getShortName() ?? $instrument?->getName(),
        );
    }

    /**
     * A security unknown to the catalogue is taken to trade in roubles on MOEX and in dollars elsewhere.
     */
    private function currency(ManualLedgerState $state, InstrumentRef $instrument): string
    {
        return ($state->instruments[$instrument->uid] ?? null)?->getCurrency()
            ?? ($instrument->stockMarket === 'MOEX' ? 'RUB' : 'USD');
    }

    /**
     * @param numeric-string $amount
     */
    private function addCash(ManualLedgerState $state, string $currency, string $amount): void
    {
        $state->cash[$currency] = bcadd($state->cash[$currency] ?? '0', $amount, 4);
    }

    private static function kind(?Instrument $instrument): ?InstrumentKind
    {
        return match (true) {
            $instrument instanceof Share => InstrumentKind::Share,
            $instrument instanceof Bond => InstrumentKind::Bond,
            $instrument instanceof Future => InstrumentKind::Future,
            default => null,
        };
    }

    /**
     * The lot a part was split off: parts are keyed by the opening operation and a suffix.
     */
    private static function origin(string $lot): string
    {
        return explode('#', $lot, 2)[0];
    }
}
