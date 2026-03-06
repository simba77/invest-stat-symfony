<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination;

final readonly class PageRequestFactory
{
    public const int DEFAULT_PER_PAGE = 20;
    public const int MAX_PER_PAGE = 100;

    public function create(int $page, int $perPage): PageRequestDTO
    {
        $normalizedPage = max(1, $page);
        $normalizedPerPage = $this->normalizePerPage($perPage);
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
