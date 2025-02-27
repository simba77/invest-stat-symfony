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
    public function getPrevPrice(): string
    {
        return $this->prevPrice;
    }

    #[\Override]
    public function getLotSize(): ?string
    {
        return $this->lotSize;
    }

    #[\Override]
    public function getIsin(): ?string
    {
        return $this->isin;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getType(): int
    {
        return $this->type;
    }

    #[\Override]
    public function getClassCode(): string
    {
        return $this->classCode;
    }
}
