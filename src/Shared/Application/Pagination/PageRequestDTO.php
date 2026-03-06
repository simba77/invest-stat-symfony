<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination;

final readonly class PageRequestDTO
{
    public function __construct(
        public int $page,
        public int $perPage,
        public int $offset,
    ) {
    }
}
