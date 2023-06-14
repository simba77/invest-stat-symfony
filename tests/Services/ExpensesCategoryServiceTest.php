<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\ExpensesCategory;
use App\Entity\User;
use App\Models\ExpensesCategoryListItem;
use App\Models\ExpensesCategoryListResponse;
use App\Repository\ExpensesCategoryRepository;
use App\Services\ExpensesCategoryService;
use Doctrine\Common\Collections\Criteria;
use PHPUnit\Framework\TestCase;

class ExpensesCategoryServiceTest extends TestCase
{
    public function testGetCategories(): void
    {
        $user = (new User())->setName('test')->setId(1);

        $repository = $this->createMock(ExpensesCategoryRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(
                ['user_id' => $user->getId()],
                ['name' => Criteria::DESC]
            )
            ->willReturn(
                [
                    (new ExpensesCategory())->setId(1)->setName('Test')->setUserId(1),
                ]
            );

        $service = new ExpensesCategoryService($repository);
        $expected = new ExpensesCategoryListResponse([new ExpensesCategoryListItem(id: 1, name: 'Test')]);

        $this->assertEquals($expected, $service->getCategories($user));
    }
}
