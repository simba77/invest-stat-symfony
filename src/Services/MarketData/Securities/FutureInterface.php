<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

interface FutureInterface
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

    public function getPrice(): float;

    public function getLotSize(): ?float;

    public function getExpiration(): ?\DateTimeInterface;

    public function getStepPrice(): ?float;
}