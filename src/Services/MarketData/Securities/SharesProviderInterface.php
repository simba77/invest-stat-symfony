<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

interface SharesProviderInterface
{
    /**
     * @return ShareInterface[]
     */
    public function getShares(): array;
}
