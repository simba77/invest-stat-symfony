<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination;

final readonly class PaginationMetaFactory
{
    public function create(int $page, int $perPage, int $totalItems): PaginationMetaDTO
    {
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min(max(1, $page), $totalPages);

        return new PaginationMetaDTO(
            page: $currentPage,
            perPage: $perPage,
            totalItems: $totalItems,
            totalPages: $totalPages,
            hasPrev: $currentPage > 1,
            hasNext: $currentPage < $totalPages,
        );
    }
}
