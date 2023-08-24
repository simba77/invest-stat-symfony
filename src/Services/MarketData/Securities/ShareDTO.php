<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

class ShareDTO implements ShareInterface
{
    public function __construct(
        public string $ticker,
        public string $name,
        public string $stockMarket,
        public string $currency,
        public float $price,
        public int $type,
        public ?string $shortName,
        public ?string $latName,
        public ?float $lotSize,
        public ?string $isin,
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

    public function getIsin(): ?string
    {
        return $this->isin;
    }

    /**
     * @inheritDoc
     */
    public function getType(): int
    {
        return $this->type;
    }
}
