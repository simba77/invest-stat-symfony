<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

class ShareDTO implements ShareInterface
{
    public function __construct(
        public string $ticker,
        public string $name,
        public string $stockMarket,
        public string $currency,
        public string $price,
        public int $type,
        public ?string $shortName,
        public ?string $latName,
        public ?string $lotSize,
        public ?string $isin,
        public ?string $prevPrice,
        public ?string $classCode,
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

    public function getPrevPrice(): string
    {
        return $this->prevPrice;
    }

    public function getLotSize(): ?string
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

    public function getClassCode(): string
    {
        return $this->classCode;
    }
}
