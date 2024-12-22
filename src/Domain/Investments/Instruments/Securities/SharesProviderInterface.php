<?php

declare(strict_types=1);

namespace App\Domain\Investments\Instruments\Securities;

interface SharesProviderInterface
{
    /**
     * @return ShareInterface[]
     */
    public function getShares(): array;
}
