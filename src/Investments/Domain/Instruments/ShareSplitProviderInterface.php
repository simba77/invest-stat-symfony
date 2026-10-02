<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

interface ShareSplitProviderInterface
{
    /**
     * @return list<ShareSplit> not persisted
     */
    public function getSplits(): array;
}
