<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

interface FuturesProviderInterface
{
    /**
     * @return FutureInterface[]
     */
    public function getFutures(): array;
}
