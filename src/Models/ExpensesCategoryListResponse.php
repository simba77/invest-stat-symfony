<?php

declare(strict_types=1);

namespace App\Models;

class ExpensesCategoryListResponse
{
    /**
     * @param ExpensesCategoryListItem[] $items
     */
    public function __construct(private readonly array $items)
    {
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
