<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination;

final readonly class PageRequestFactory
{
    public const int DEFAULT_PER_PAGE = 20;
    public const int MAX_PER_PAGE = 100;

    /**
     * A page past the last one is served as the last page, so the items always match
     * the page number reported in the pagination meta.
     */
    public function create(int $page, int $perPage, int $totalItems): PageRequestDTO
    {
        $normalizedPerPage = $this->normalizePerPage($perPage);
        $lastPage = max(1, (int) ceil($totalItems / $normalizedPerPage));
        $normalizedPage = min(max(1, $page), $lastPage);
        $offset = ($normalizedPage - 1) * $normalizedPerPage;

        return new PageRequestDTO(
            page: $normalizedPage,
            perPage: $normalizedPerPage,
            offset: $offset,
        );
    }

    private function normalizePerPage(int $perPage): int
    {
        if ($perPage < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min($perPage, self::MAX_PER_PAGE);
    }
}
