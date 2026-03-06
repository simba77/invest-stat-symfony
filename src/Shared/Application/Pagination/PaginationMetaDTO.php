<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination;

final readonly class PaginationMetaDTO
{
    public function __construct(
        public int $page,
        public int $perPage,
        public int $totalItems,
        public int $totalPages,
        public bool $hasPrev,
        public bool $hasNext,
    ) {
    }
}
