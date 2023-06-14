<?php

declare(strict_types=1);

namespace App\Models;

class ExpensesCategoryListItem
{
    public function __construct(
        private int $id,
        private string $name
    ) {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
