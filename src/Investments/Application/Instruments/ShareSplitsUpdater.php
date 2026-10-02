<?php

declare(strict_types=1);

namespace App\Investments\Application\Instruments;

use App\Investments\Domain\Instruments\ShareSplit;
use App\Investments\Domain\Instruments\ShareSplitProviderInterface;
use App\Investments\Domain\Instruments\ShareSplitRepositoryInterface;

final readonly class ShareSplitsUpdater
{
    public function __construct(
        private ShareSplitProviderInterface $provider,
        private ShareSplitRepositoryInterface $repository,
    ) {
    }

    /**
     * @return int the number of splits that were not known yet
     */
    public function update(): int
    {
        $known = [];
        foreach ($this->repository->findAll() as $split) {
            $known[$this->key($split)] = true;
        }

        $added = 0;
        foreach ($this->provider->getSplits() as $split) {
            if (isset($known[$this->key($split)])) {
                continue;
            }
            $this->repository->save($split);
            $known[$this->key($split)] = true;
            $added++;
        }

        return $added;
    }

    private function key(ShareSplit $split): string
    {
        return implode('|', [$split->getStockMarket(), $split->getTicker(), $split->getTradeDate()->format('Y-m-d')]);
    }
}
