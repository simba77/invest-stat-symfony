<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Ledger;

/**
 * A dividend or a coupon together with the tax withheld from it.
 */
final class Payout
{
    /** @var numeric-string */
    private string $tax = '0';

    /**
     * @param numeric-string $gross
     */
    public function __construct(
        private readonly string $externalId,
        private readonly InstrumentRef $instrument,
        private readonly \DateTimeImmutable $paidAt,
        private readonly string $gross,
        private readonly string $currency,
    ) {
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getInstrument(): InstrumentRef
    {
        return $this->instrument;
    }

    public function getPaidAt(): \DateTimeImmutable
    {
        return $this->paidAt;
    }

    /**
     * @return numeric-string
     */
    public function getGross(): string
    {
        return $this->gross;
    }

    /**
     * @return numeric-string
     */
    public function getTax(): string
    {
        return $this->tax;
    }

    /**
     * What reached the account.
     *
     * @return numeric-string
     */
    public function getNet(): string
    {
        return bcsub($this->gross, $this->tax, 9);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param numeric-string $tax
     */
    public function withhold(string $tax): void
    {
        $this->tax = bcadd($this->tax, $tax, 9);
    }
}
