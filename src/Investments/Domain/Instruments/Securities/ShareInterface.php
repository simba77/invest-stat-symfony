<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

interface ShareInterface
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

    public function getIsin(): ?string;

    /**
     * Type constant. Stock, ETF etc.
     */
    public function getType(): int;

    /**
     * Type constant. TQBR, TQTF etc.
     */
    public function getClassCode(): string;
}
