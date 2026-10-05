<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Ledger;

use App\Investments\Domain\Operations\Deals\DealType;

/**
 * Open lots per instrument. Broker trades are matched first in, first out, and a sale without
 * long lots opens a short. Manual trades open and close lots explicitly, by key or by quantity.
 */
final class LotBook
{
    /** @var list<Lot> every lot in the order it was opened or split off */
    private array $lots = [];

    /** @var array<string, list<Lot>> open lots by instrument uid, oldest first */
    private array $open = [];

    /** @var array<string, int> parts split off each opening operation */
    private array $parts = [];

    /**
     * @param numeric-string $price
     * @param numeric-string $commission for the whole trade
     */
    public function buy(InstrumentRef $instrument, string $operationId, int $quantity, string $price, \DateTimeImmutable $at, string $commission, string $currency): void
    {
        $this->trade($instrument, DealType::Long, $operationId, $quantity, $price, $at, $commission, $currency);
    }

    /**
     * @param numeric-string $price
     * @param numeric-string $commission for the whole trade
     */
    public function sell(InstrumentRef $instrument, string $operationId, int $quantity, string $price, \DateTimeImmutable $at, string $commission, string $currency): void
    {
        $this->trade($instrument, DealType::Short, $operationId, $quantity, $price, $at, $commission, $currency);
    }

    /**
     * Opens a lot under the given key, whatever lots of the instrument are open.
     *
     * @param numeric-string $price
     * @param numeric-string $commission
     * @param numeric-string|null $target
     */
    public function open(
        InstrumentRef $instrument,
        string $key,
        DealType $direction,
        int $quantity,
        string $price,
        \DateTimeImmutable $at,
        string $commission,
        string $currency,
        ?string $target = null,
    ): Lot {
        $lot = new Lot($key, $key, $instrument, $direction, $quantity, $price, $at, $commission, $currency, $target);
        $this->lots[] = $lot;
        $this->open[$instrument->uid][] = $lot;

        return $lot;
    }

    /**
     * Closes the lot with the key, or, without a key, the oldest lots of the instrument that are not
     * blocked. Closes no more than is open.
     *
     * @param numeric-string $price
     * @param numeric-string $commission of the whole trade
     * @return list<Lot> the closed parts
     */
    public function close(string $uid, ?string $key, int $quantity, string $price, \DateTimeImmutable $at, string $commission): array
    {
        $lot = $key !== null ? $this->findOpen($key) : null;
        $candidates = $key !== null
            ? ($lot !== null ? [$lot] : [])
            : array_values(array_filter($this->open[$uid] ?? [], static fn (Lot $lot) => ! $lot->isBlocked()));

        $closed = [];
        $left = $quantity;
        foreach ($candidates as $lot) {
            if ($left <= 0) {
                break;
            }
            $part = $this->closeLot($lot, $left, $price, $at, $commission, $quantity);
            $left -= $part->getQuantity();
            $closed[] = $part;
        }

        return $closed;
    }

    public function findOpen(string $key): ?Lot
    {
        foreach ($this->open as $lots) {
            foreach ($lots as $lot) {
                if ($lot->getKey() === $key) {
                    return $lot;
                }
            }
        }

        return null;
    }

    /**
     * Takes securities out without a sale price: the lots close at their own price, with no result.
     */
    public function withdraw(string $uid, int $quantity, \DateTimeImmutable $at): void
    {
        foreach ($this->open[$uid] ?? [] as $lot) {
            if ($quantity <= 0) {
                break;
            }
            $quantity -= $this->closeLot($lot, $quantity, $lot->getOpenPrice(), $at, '0', 1)->getQuantity();
        }
    }

    /**
     * @return list<string> what could not be applied exactly
     */
    public function applySplit(string $ticker, string $stockMarket, int $before, int $after): array
    {
        $warnings = [];
        foreach ($this->open as $lots) {
            foreach ($lots as $lot) {
                $instrument = $lot->getInstrument();
                if ($instrument->ticker !== $ticker || $instrument->stockMarket !== $stockMarket) {
                    continue;
                }
                $remainder = $lot->applySplit($before, $after);
                if ($remainder !== 0) {
                    $warnings[] = sprintf('%s: the split %d:%d leaves a fraction of a share in lot %s', $ticker, $before, $after, $lot->getKey());
                }
            }
        }

        return $warnings;
    }

    /**
     * Spreads a repayment of the nominal over the open long lots of a bond.
     *
     * @param numeric-string $amount
     */
    public function repay(string $uid, string $amount): bool
    {
        $quantity = $this->openQuantity($uid);
        if ($quantity <= 0) {
            return false;
        }

        $perSecurity = bcdiv($amount, (string) $quantity, 9);
        foreach ($this->open[$uid] ?? [] as $lot) {
            $lot->reduceOpenPrice($perSecurity);
        }

        return true;
    }

    /**
     * Long quantity is positive, short is negative.
     */
    public function openQuantity(string $uid): int
    {
        $quantity = 0;
        foreach ($this->open[$uid] ?? [] as $lot) {
            $quantity += $lot->getDirection() === DealType::Long ? $lot->getQuantity() : -$lot->getQuantity();
        }

        return $quantity;
    }

    /**
     * @return array<string, int>
     */
    public function positions(): array
    {
        $positions = [];
        foreach (array_keys($this->open) as $uid) {
            $quantity = $this->openQuantity($uid);
            if ($quantity !== 0) {
                $positions[$uid] = $quantity;
            }
        }

        return $positions;
    }

    /**
     * @return list<Lot>
     */
    public function lots(): array
    {
        return $this->lots;
    }

    /**
     * Closes lots of the opposite direction first, then opens a lot with what is left.
     *
     * @param DealType $side Long for a purchase, Short for a sale
     * @param numeric-string $price
     * @param numeric-string $commission
     */
    private function trade(InstrumentRef $instrument, DealType $side, string $operationId, int $quantity, string $price, \DateTimeImmutable $at, string $commission, string $currency): void
    {
        $left = $quantity;
        foreach ($this->open[$instrument->uid] ?? [] as $lot) {
            if ($left <= 0 || $lot->getDirection() === $side) {
                break;
            }
            $left -= $this->closeLot($lot, $left, $price, $at, $commission, $quantity)->getQuantity();
        }

        if ($left > 0) {
            $lot = new Lot(
                key:            $operationId,
                origin:         $operationId,
                instrument:     $instrument,
                direction:      $side,
                quantity:       $left,
                openPrice:      $price,
                openedAt:       $at,
                openCommission: $this->share($commission, $left, $quantity),
                currency:       $currency,
            );
            $this->lots[] = $lot;
            $this->open[$instrument->uid][] = $lot;
        }
    }

    /**
     * Closes up to $quantity of the lot; a bigger lot is split and its rest stays open in its place.
     *
     * @param numeric-string $price
     * @param numeric-string $commission of the whole trade
     * @return Lot the closed part
     */
    private function closeLot(Lot $lot, int $quantity, string $price, \DateTimeImmutable $at, string $commission, int $tradeQuantity): Lot
    {
        $uid = $lot->getInstrument()->uid;
        $position = array_search($lot, $this->open[$uid] ?? [], true);
        if ($position === false) {
            throw new \LogicException('The lot is not open');
        }

        if ($quantity < $lot->getQuantity()) {
            $rest = $lot->splitOff($quantity, $this->nextPartKey($lot->getOrigin()));
            $this->lots[] = $rest;
            array_splice($this->open[$uid], $position, 1, [$rest]);
        } else {
            array_splice($this->open[$uid], $position, 1);
        }

        $lot->close($price, $at, $this->share($commission, $lot->getQuantity(), $tradeQuantity));

        return $lot;
    }

    private function nextPartKey(string $origin): string
    {
        $this->parts[$origin] = ($this->parts[$origin] ?? 0) + 1;

        return $origin . '#' . $this->parts[$origin];
    }

    /**
     * @param numeric-string $amount
     * @return numeric-string
     */
    private function share(string $amount, int $quantity, int $total): string
    {
        return bcdiv(bcmul($amount, (string) $quantity, 9), (string) $total, 9);
    }
}
