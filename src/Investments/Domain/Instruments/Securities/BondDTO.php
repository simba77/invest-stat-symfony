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

    #[\Override]
    public function getTicker(): string
    {
        return $this->ticker;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    #[\Override]
    public function getLatName(): ?string
    {
        return $this->latName;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getStockMarket(): string
    {
        return $this->stockMarket;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getCurrency(): string
    {
        return $this->currency;
    }

    #[\Override]
    public function getPrice(): string
    {
        return $this->price;
    }

    #[\Override]
    public function getPrevPrice(): ?string
    {
        return $this->prevPrice;
    }

    #[\Override]
    public function getLotSize(): ?string
    {
        return $this->lotSize;
    }

    #[\Override]
    public function getStepPrice(): ?string
    {
        return $this->stepPrice;
    }

    #[\Override]
    public function getCouponPercent(): ?string
    {
        return $this->couponPercent;
    }

    #[\Override]
    public function getCouponValue(): ?string
    {
        return $this->couponValue;
    }

    #[\Override]
    public function getCouponAccumulated(): ?string
    {
        return $this->couponAccumulated;
    }

    #[\Override]
    public function getNextCouponDate(): ?\DateTimeInterface
    {
        return $this->nextCouponDate;
    }

    #[\Override]
    public function getMaturityDate(): ?\DateTimeInterface
    {
        return $this->maturityDate;
    }
}
