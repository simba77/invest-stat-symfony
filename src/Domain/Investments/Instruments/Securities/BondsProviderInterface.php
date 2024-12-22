<?php

declare(strict_types=1);

namespace App\Domain\Investments\Instruments\Securities;

interface BondsProviderInterface
{
    /**
     * @return BondInterface[]
     */
    public function getBonds(): array;
}
