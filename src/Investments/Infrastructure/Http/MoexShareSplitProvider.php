<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Http;

use App\Investments\Domain\Instruments\ShareSplit;
use App\Investments\Domain\Instruments\ShareSplitProviderInterface;

final readonly class MoexShareSplitProvider implements ShareSplitProviderInterface
{
    public function __construct(
        private MoexHttpClient $moexHttpClient,
    ) {
    }

    #[\Override]
    public function getSplits(): array
    {
        $splits = [];
        foreach ($this->moexHttpClient->getSplits() as $row) {
            $tradeDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $row['tradedate']);
            if ($tradeDate === false || (int) $row['before'] <= 0 || (int) $row['after'] <= 0) {
                continue;
            }

            $splits[] = new ShareSplit($row['secid'], 'MOEX', $tradeDate, (int) $row['before'], (int) $row['after']);
        }

        return $splits;
    }
}
