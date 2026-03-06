<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination;

/**
 * @template TItem
 */
final readonly class PaginatedResponseDTO
{
    /**
     * @param list<TItem> $items
     */
    public function __construct(
        public array $items,
        public PaginationMetaDTO $pagination,
    ) {
    }
}
