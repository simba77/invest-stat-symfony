<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\History;

final readonly class OperationHistoryFilter
{
    public function __construct(
        public ?int $accountId = null,
        public ?OperationCategory $category = null,
    ) {
    }
}
