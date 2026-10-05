<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Ledger;

use App\Investments\Domain\Operations\Deals\DealType;

/**
 * A quantity of a security opened by one trade and, once closed, closed at one price.
 * Prices are per security in the trade currency; commissions are totals for the lot.
 */
final class Lot
{
    /** @var numeric-string|null */
    private ?string $closePrice = null;

    private ?\DateTimeImmutable $closedAt = null;

    /** @var numeric-string */
    private string $closeCommission = '0';

    /** A blocked lot is held but cannot be traded: sales by quantity pass it by. */
    private bool $blocked = false;

    /**
     * @param numeric-string $openPrice
     * @param numeric-string $openCommission
     */
    public function __construct(
        private readonly string $key,
        private readonly string $origin,
        private readonly InstrumentRef $instrument,
        private readonly DealType $direction,
        private int $quantity,
        private string $openPrice,
        private readonly \DateTimeImmutable $openedAt,
        private string $openCommission,
        private readonly string $currency,
        private readonly ?string $target = null,
    ) {
    }

    /**
     * Stable identity of the lot between syncs: the opening operation, plus a suffix for every part split off it.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * The id of the operation that opened the lot.
     */
    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getInstrument(): InstrumentRef
    {
        return $this->instrument;
    }

    public function getDirection(): DealType
    {
        return $this->direction;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return numeric-string
     */
    public function getOpenPrice(): string
    {
        return $this->openPrice;
    }

    public function getOpenedAt(): \DateTimeImmutable
    {
        return $this->openedAt;
    }

    /**
     * @return numeric-string
     */
    public function getOpenCommission(): string
    {
        return $this->openCommission;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return numeric-string|null
     */
    public function getClosePrice(): ?string
    {
        return $this->closePrice;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    /**
     * @return numeric-string
     */
    public function getCloseCommission(): string
    {
        return $this->closeCommission;
    }

    public function isOpen(): bool
    {
        return $this->closedAt === null;
    }

    /**
     * The price the owner aims to close the lot at.
     *
     * @return numeric-string|null
     */
    public function getTarget(): ?string
    {
        /** @var numeric-string|null */
        return $this->target;
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function setBlocked(bool $blocked): void
    {
        $this->blocked = $blocked;
    }

    /**
     * Keeps $quantity in this lot and moves the rest, with its share of the commission, into a new lot.
     */
    public function splitOff(int $quantity, string $key): self
    {
        if ($quantity <= 0 || $quantity >= $this->quantity) {
            throw new \InvalidArgumentException('A lot can only be split into two non-empty parts');
        }

        $restQuantity = $this->quantity - $quantity;
        $keptCommission = bcdiv(bcmul($this->openCommission, (string) $quantity, 9), (string) $this->quantity, 9);
        $rest = new self(
            key:            $key,
            origin:         $this->origin,
            instrument:     $this->instrument,
            direction:      $this->direction,
            quantity:       $restQuantity,
            openPrice:      $this->openPrice,
            openedAt:       $this->openedAt,
            openCommission: bcsub($this->openCommission, $keptCommission, 9),
            currency:       $this->currency,
            target:         $this->target,
        );
        $rest->blocked = $this->blocked;

        $this->quantity = $quantity;
        $this->openCommission = $keptCommission;

        return $rest;
    }

    /**
     * @param numeric-string $price
     * @param numeric-string $commission
     */
    public function close(string $price, \DateTimeImmutable $closedAt, string $commission): void
    {
        $this->closePrice = $price;
        $this->closedAt = $closedAt;
        $this->closeCommission = $commission;
    }

    /**
     * Applies a split of `before` shares into `after` shares; returns the shares that do not make a whole one.
     */
    public function applySplit(int $before, int $after): int
    {
        $shares = $this->quantity * $after;
        $this->quantity = intdiv($shares, $before);
        $this->openPrice = bcdiv(bcmul($this->openPrice, (string) $before, 9), (string) $after, 9);

        return $shares % $before;
    }

    /**
     * Part of the nominal was repaid: the rest of the bond cost less.
     *
     * @param numeric-string $amountPerSecurity
     */
    public function reduceOpenPrice(string $amountPerSecurity): void
    {
        $this->openPrice = bcsub($this->openPrice, $amountPerSecurity, 9);
    }
}
