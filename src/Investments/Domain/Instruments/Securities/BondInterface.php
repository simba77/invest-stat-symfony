<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

interface BondInterface
{
    public function getTicker(): string;

    public function getName(): string;

    public function getShortName(): ?string;

    public function getLatName(): ?string;

    /**
     * Stock market. E.g. MOEX, SPB
     */
    public function getStockMarket(): string;

    /**
     * Currency code. E.g. USD, RUB
     */
    public function getCurrency(): string;

    public function getPrice(): string;

    public function getPrevPrice(): ?string;

    public function getLotSize(): ?string;

    public function getStepPrice(): ?string;

    public function getCouponPercent(): ?string;

    public function getCouponValue(): ?string;

    public function getCouponAccumulated(): ?string;

    public function getNextCouponDate(): ?\DateTimeInterface;

    public function getMaturityDate(): ?\DateTimeInterface;
}
