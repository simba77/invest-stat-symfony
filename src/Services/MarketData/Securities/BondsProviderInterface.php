<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

interface BondsProviderInterface
{
    /**
     * @return BondInterface[]
     */
    public function getBonds(): array;
}
