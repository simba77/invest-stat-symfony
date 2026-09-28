<?php

declare(strict_types=1);

namespace App\Tests\Expenses\Application\Controller;

use App\Expenses\Domain\Expense;
use App\Expenses\Domain\ExpensesCategory;
use App\Tests\Expenses\CreatesExpenses;
use App\Tests\Support\ApiTestCase;

final class ExpensesCategoryControllerTest extends ApiTestCase
{
    use CreatesExpenses;

    /**
     * @dataProvider protectedEndpoints
     */
    public function testRejectsAnonymousRequests(string $method, string $uri): void
    {
        $this->client->jsonRequest($method, $uri, ['name' => 'Food']);

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function protectedEndpoints(): iterable
    {
        yield 'list' => ['GET', '/api/expenses'];
        yield 'show' => ['GET', '/api/expenses/category/1'];
        yield 'create' => ['POST', '/api/expenses/category/create'];
        yield 'edit' => ['POST', '/api/expenses/category/edit/1'];
        yield 'delete' => ['POST', '/api/expenses/category/delete/1'];
    }

    public function testIndexListsOwnCategoriesWithTheirExpenses(): void
    {
        $admin = $this->admin();
        $food = $this->createCategory($admin, 'Food');
        $groceries = $this->createExpense($food, 'Groceries', '150.50');
        $cafe = $this->createExpense($food, 'Cafe', '49.50');
        $car = $this->createCategory($admin, 'Car');
        $this->createCategory($this->otherUser(), 'Not mine');
        $this->loginAs($admin);

        $this->getJson('/api/expenses');

        self::assertResponseIsSuccessful();
        self::assertSame([
            'items' => [
                [
                    'id'       => $food->getId(),
                    'name'     => 'Food',
                    'expenses' => [
                        ['id' => $groceries->getId(), 'name' => 'Groceries', 'sum' => '150.50'],
                        ['id' => $cafe->getId(), 'name' => 'Cafe', 'sum' => '49.50'],
                    ],
                ],
                ['id' => $car->getId(), 'name' => 'Car', 'expenses' => []],
            ],
        ], $this->responseJson());
    }

    public function testCreateAddsCategoryForCurrentUser(): void
    {
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/expenses/category/create', ['name' => 'Travel']);

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        $categories = $this->findFreshBy(ExpensesCategory::class, ['userId' => $admin->getId()]);
        self::assertCount(1, $categories);
        self::assertSame('Travel', $categories[0]->getName());
    }

    /**
     * @dataProvider invalidNames
     */
    public function testCreateRejectsInvalidName(string $name): void
    {
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/expenses/category/create', ['name' => $name]);

        $this->assertViolatedFields(['name']);
        self::assertSame([], $this->findFreshBy(ExpensesCategory::class, ['userId' => $admin->getId()]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNames(): iterable
    {
        yield 'blank' => [''];
        yield 'shorter than 3 characters' => ['ab'];
        yield 'longer than 200 characters' => [str_repeat('a', 201)];
    }

    public function testShowReturnsCategory(): void
    {
        $admin = $this->admin();
        $category = $this->createCategory($admin, 'Food');
        $this->loginAs($admin);

        $this->getJson('/api/expenses/category/' . $category->getId());

        self::assertResponseIsSuccessful();
        self::assertSame(['id' => $category->getId(), 'name' => 'Food', 'expenses' => []], $this->responseJson());
    }

    public function testShowHidesOtherUsersCategory(): void
    {
        $category = $this->createCategory($this->otherUser());
        $this->loginAs($this->admin());

        $this->getJson('/api/expenses/category/' . $category->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditRenamesCategory(): void
    {
        $admin = $this->admin();
        $category = $this->createCategory($admin, 'Food');
        $this->loginAs($admin);

        $this->postJson('/api/expenses/category/edit/' . $category->getId(), ['name' => 'Groceries']);

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        self::assertSame('Groceries', $this->findFresh(ExpensesCategory::class, $category->getId())?->getName());
    }

    public function testEditRejectsInvalidName(): void
    {
        $admin = $this->admin();
        $category = $this->createCategory($admin, 'Food');
        $this->loginAs($admin);

        $this->postJson('/api/expenses/category/edit/' . $category->getId(), ['name' => 'ab']);

        $this->assertViolatedFields(['name']);
        self::assertSame('Food', $this->findFresh(ExpensesCategory::class, $category->getId())?->getName());
    }

    public function testEditLeavesOtherUsersCategoryUntouched(): void
    {
        $category = $this->createCategory($this->otherUser(), 'Food');
        $this->loginAs($this->admin());

        $this->postJson('/api/expenses/category/edit/' . $category->getId(), ['name' => 'Renamed']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame('Food', $this->findFresh(ExpensesCategory::class, $category->getId())?->getName());
    }

    public function testDeleteRemovesCategoryWithItsExpenses(): void
    {
        $admin = $this->admin();
        $category = $this->createCategory($admin);
        $expense = $this->createExpense($category);
        $this->loginAs($admin);

        $this->postJson('/api/expenses/category/delete/' . $category->getId());

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        self::assertNull($this->findFresh(ExpensesCategory::class, $category->getId()));
        self::assertNull($this->findFresh(Expense::class, $expense->getId()));
    }

    public function testDeleteLeavesOtherUsersCategoryUntouched(): void
    {
        $category = $this->createCategory($this->otherUser());
        $this->loginAs($this->admin());

        $this->postJson('/api/expenses/category/delete/' . $category->getId());

        self::assertResponseStatusCodeSame(404);
        self::assertNotNull($this->findFresh(ExpensesCategory::class, $category->getId()));
    }
}
