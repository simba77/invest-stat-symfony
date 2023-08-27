<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

interface FuturesProviderInterface
{
    /**
     * @return FutureInterface[]
     */
    public function getFutures(): array;
}
