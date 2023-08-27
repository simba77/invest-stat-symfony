<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

class FutureDTO implements FutureInterface
{
    public function __construct(
        public string $ticker,
        public string $name,
        public string $stockMarket,
        public string $currency,
        public float $price,
        public ?string $shortName,
        public ?string $latName,
        public ?float $lotSize,
        public ?\DateTimeInterface $expiration,
        public ?float $stepPrice
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

    public function getLotSize(): ?float
    {
        return $this->lotSize;
    }

    public function getExpiration(): ?\DateTimeInterface
    {
        return $this->expiration;
    }

    public function getStepPrice(): ?float
    {
        return $this->stepPrice;
    }
}
