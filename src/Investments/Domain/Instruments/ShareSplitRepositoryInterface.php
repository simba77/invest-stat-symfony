<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

interface ShareSplitRepositoryInterface
{
    /**
     * @return list<ShareSplit> ordered by trade date
     */
    public function findAll(): array;

    public function save(ShareSplit $split): void;
}
