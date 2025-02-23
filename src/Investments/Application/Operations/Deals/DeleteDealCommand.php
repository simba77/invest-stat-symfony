<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

class DeleteDealCommand
{
    public function __construct(
        public int $id,
    ) {
    }
}
