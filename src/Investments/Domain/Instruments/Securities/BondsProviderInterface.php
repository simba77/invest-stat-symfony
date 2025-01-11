<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

interface BondsProviderInterface
{
    /**
     * @return BondInterface[]
     */
    public function getBonds(): array;
}
