<?php

declare(strict_types=1);

namespace App\Expenses\Application;

use App\Expenses\Domain\ExpensesCategory;

class DeleteCategoryCommand
{
    public function __construct(public ExpensesCategory $category)
    {
    }
}
