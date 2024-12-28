<?php

declare(strict_types=1);

namespace App\Domain\Investments\Instruments\Securities;

interface FuturesProviderInterface
{
    /**
     * @return FutureInterface[]
     */
    public function getFutures(): array;
}