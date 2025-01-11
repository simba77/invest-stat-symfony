<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

interface SharesProviderInterface
{
    /**
     * @return ShareInterface[]
     */
    public function getShares(): array;
}
