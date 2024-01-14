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
        public string $price,
        public ?string $prevPrice,
        public ?string $shortName,
        public ?string $latName,
        public ?string $lotSize,
        public ?\DateTimeInterface $expiration,
        public ?string $stepPrice
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

    public function getExpiration(): ?\DateTimeInterface
    {
        return $this->expiration;
    }

    public function getStepPrice(): ?string
    {
        return $this->stepPrice;
    }
}
