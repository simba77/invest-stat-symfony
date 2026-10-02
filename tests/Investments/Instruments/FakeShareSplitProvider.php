<?php

declare(strict_types=1);

namespace App\Tests\Investments\Instruments;

use App\Investments\Domain\Instruments\ShareSplit;
use App\Investments\Domain\Instruments\ShareSplitProviderInterface;

/**
 * Replaces MOEX in the test environment (config/services.yaml).
 */
final class FakeShareSplitProvider implements ShareSplitProviderInterface
{
    /** @var list<ShareSplit> */
    public array $splits = [];

    #[\Override]
    public function getSplits(): array
    {
        return $this->splits;
    }
}
