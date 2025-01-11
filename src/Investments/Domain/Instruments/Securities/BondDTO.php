<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

class BondDTO implements BondInterface
{
    public function __construct(
        public string $ticker,
        public string $name,
        public string $stockMarket,
        public string $currency,
        public string $price,
        public ?string $prevPrice,
        public ?string $shortName,
        public ?string $latName,
        public ?string $lotSize,
        public ?string $stepPrice,
        public ?string $couponPercent,
        public ?string $couponValue,
        public ?string $couponAccumulated,
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

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getPrevPrice(): ?string
    {
        return $this->prevPrice;
    }

    public function getLotSize(): ?string
    {
        return $this->lotSize;
    }

    public function getStepPrice(): ?string
    {
        return $this->stepPrice;
    }

    public function getCouponPercent(): ?string
    {
        return $this->couponPercent;
    }

    public function getCouponValue(): ?string
    {
        return $this->couponValue;
    }

    public function getCouponAccumulated(): ?string
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
