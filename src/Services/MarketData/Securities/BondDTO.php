<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

class BondDTO implements BondInterface
{
    public function __construct(
        public string $ticker,
        public string $name,
        public string $stockMarket,
        public string $currency,
        public float $price,
        public ?float $prevPrice,
        public ?string $shortName,
        public ?string $latName,
        public ?float $lotSize,
        public ?float $stepPrice,
        public ?float $couponPercent,
        public ?float $couponValue,
        public ?float $couponAccumulated,
        public ?\DateTimeInterface $nextCouponDate,
        public ?\DateTimeInterface $maturityDate,
    ) {
    }

    public function getTicker(): string
    {
        return $this->ticker;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function getLatName(): ?string
    {
        return $this->latName;
    }

    /**
     * @inheritDoc
     */
    public function getStockMarket(): string
    {
        return $this->stockMarket;
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getPrevPrice(): ?float
    {
        return $this->prevPrice;
    }

    public function getLotSize(): ?float
    {
        return $this->lotSize;
    }

    public function getStepPrice(): ?float
    {
        return $this->stepPrice;
    }

    public function getCouponPercent(): ?float
    {
        return $this->couponPercent;
    }

    public function getCouponValue(): ?float
    {
        return $this->couponValue;
    }

    public function getCouponAccumulated(): ?float
    {
        return $this->couponAccumulated;
    }

    public function getNextCouponDate(): ?\DateTimeInterface
    {
        return $this->nextCouponDate;
    }

    public function getMaturityDate(): ?\DateTimeInterface
    {
        return $this->maturityDate;
    }
}
