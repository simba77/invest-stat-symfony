<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

final readonly class BlockDealCommand
{
    public function __construct(
        public int $id,
        public bool $blocked,
    ) {
    }
}
