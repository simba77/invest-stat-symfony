<?php

declare(strict_types=1);

namespace App\Tests\Expenses\Application\Controller;

use App\Expenses\Domain\Expense;
use App\Expenses\Domain\ExpensesCategory;
use App\Tests\Expenses\CreatesExpenses;
use App\Tests\Support\ApiTestCase;

final class ExpenseControllerTest extends ApiTestCase
{
    use CreatesExpenses;

    /**
     * @dataProvider protectedEndpoints
     */
    public function testRejectsAnonymousRequests(string $method, string $uri): void
    {
        $this->client->jsonRequest($method, $uri, ['name' => 'Groceries', 'sum' => '100']);

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function protectedEndpoints(): iterable
    {
        yield 'create' => ['POST', '/api/expenses/1/create'];
        yield 'show' => ['GET', '/api/expenses/expense/1'];
        yield 'edit' => ['POST', '/api/expenses/expense/edit/1'];
        yield 'delete' => ['POST', '/api/expenses/expense/delete/1'];
        yield 'summary' => ['GET', '/api/expenses/summary'];
    }

    public function testCreateAddsExpenseToCategory(): void
    {
        $admin = $this->admin();
        $category = $this->createCategory($admin);
        $this->loginAs($admin);

        $this->postJson(sprintf('/api/expenses/%d/create', $category->getId()), ['name' => 'Groceries', 'sum' => '150.50']);

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        $expenses = $this->findFreshBy(Expense::class, ['category' => $category->getId()]);
        self::assertCount(1, $expenses);
        self::assertSame('Groceries', $expenses[0]->getName());
        self::assertSame('150.50', $expenses[0]->getSum());
        self::assertSame($admin->getId(), $expenses[0]->getUserId());
    }

    public function testCreateRejectsOtherUsersCategory(): void
    {
        $category = $this->createCategory($this->otherUser());
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson(sprintf('/api/expenses/%d/create', $category->getId()), ['name' => 'Groceries', 'sum' => '100']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame([], $this->findFreshBy(Expense::class, ['userId' => $admin->getId()]));
    }

    /**
     * @dataProvider invalidPayloads
     *
     * @param array<string, string> $payload
     * @param list<string> $violatedFields
     */
    public function testCreateRejectsInvalidPayload(array $payload, array $violatedFields): void
    {
        $admin = $this->admin();
        $category = $this->createCategory($admin);
        $this->loginAs($admin);

        $this->postJson(sprintf('/api/expenses/%d/create', $category->getId()), $payload);

        $this->assertViolatedFields($violatedFields);
        self::assertSame([], $this->findFreshBy(Expense::class, ['userId' => $admin->getId()]));
    }

    /**
     * @return iterable<string, array{array<string, string>, list<string>}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'name shorter than 3 characters' => [['name' => 'ab', 'sum' => '100'], ['name']];
        yield 'name longer than 200 characters' => [['name' => str_repeat('a', 201), 'sum' => '100'], ['name']];
        yield 'blank sum' => [['name' => 'Groceries', 'sum' => ''], ['sum']];
        yield 'sum is not a number' => [['name' => 'Groceries', 'sum' => 'ten'], ['sum']];
        yield 'both fields blank' => [['name' => '', 'sum' => ''], ['name', 'sum']];
    }

    public function testShowReturnsExpense(): void
    {
        $admin = $this->admin();
        $expense = $this->createExpense($this->createCategory($admin), 'Groceries', '150.50');
        $this->loginAs($admin);

        $this->getJson('/api/expenses/expense/' . $expense->getId());

        self::assertResponseIsSuccessful();
        self::assertSame(['id' => $expense->getId(), 'name' => 'Groceries', 'sum' => '150.50'], $this->responseJson());
    }

    public function testShowHidesOtherUsersExpense(): void
    {
        $expense = $this->createExpense($this->createCategory($this->otherUser()));
        $this->loginAs($this->admin());

        $this->getJson('/api/expenses/expense/' . $expense->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditUpdatesExpense(): void
    {
        $admin = $this->admin();
        $category = $this->createCategory($admin);
        $expense = $this->createExpense($category, 'Groceries', '150.50');
        $this->loginAs($admin);

        $this->postJson('/api/expenses/expense/edit/' . $expense->getId(), ['name' => 'Cafe', 'sum' => '49.50']);

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        $updated = $this->findFresh(Expense::class, $expense->getId());
        self::assertSame('Cafe', $updated?->getName());
        self::assertSame('49.50', $updated?->getSum());
        self::assertSame($category->getId(), $updated?->getCategory()?->getId());
    }

    public function testEditRejectsInvalidPayload(): void
    {
        $admin = $this->admin();
        $expense = $this->createExpense($this->createCategory($admin), 'Groceries', '150.50');
        $this->loginAs($admin);

        $this->postJson('/api/expenses/expense/edit/' . $expense->getId(), ['name' => 'Groceries', 'sum' => 'ten']);

        $this->assertViolatedFields(['sum']);
        self::assertSame('150.50', $this->findFresh(Expense::class, $expense->getId())?->getSum());
    }

    public function testEditLeavesOtherUsersExpenseUntouched(): void
    {
        $expense = $this->createExpense($this->createCategory($this->otherUser()), 'Groceries', '150.50');
        $this->loginAs($this->admin());

        $this->postJson('/api/expenses/expense/edit/' . $expense->getId(), ['name' => 'Cafe', 'sum' => '1.00']);

        self::assertResponseStatusCodeSame(404);
        $untouched = $this->findFresh(Expense::class, $expense->getId());
        self::assertSame('Groceries', $untouched?->getName());
        self::assertSame('150.50', $untouched?->getSum());
    }

    public function testDeleteRemovesExpense(): void
    {
        $admin = $this->admin();
        $category = $this->createCategory($admin);
        $expense = $this->createExpense($category);
        $this->loginAs($admin);

        $this->postJson('/api/expenses/expense/delete/' . $expense->getId());

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        self::assertNull($this->findFresh(Expense::class, $expense->getId()));
        self::assertNotNull($this->findFresh(ExpensesCategory::class, $category->getId()));
    }

    public function testDeleteLeavesOtherUsersExpenseUntouched(): void
    {
        $expense = $this->createExpense($this->createCategory($this->otherUser()));
        $this->loginAs($this->admin());

        $this->postJson('/api/expenses/expense/delete/' . $expense->getId());

        self::assertResponseStatusCodeSame(404);
        self::assertNotNull($this->findFresh(Expense::class, $expense->getId()));
    }

    public function testSummaryComparesSalaryWithOwnExpenses(): void
    {
        $admin = $this->admin();
        $admin->setSalary('1000.00');
        $this->persist($admin);
        $category = $this->createCategory($admin);
        $this->createExpense($category, 'Groceries', '150.50');
        $this->createExpense($category, 'Cafe', '49.50');
        $this->createExpense($this->createCategory($this->otherUser()), 'Not mine', '999.00');
        $this->loginAs($admin);

        $this->getJson('/api/expenses/summary');

        self::assertResponseIsSuccessful();
        self::assertSame(['summary' => [
            ['name' => 'Salary', 'total' => '1000.00', 'helpText' => 'Monthly Salary'],
            ['name' => 'All Expenses', 'total' => '200.00', 'helpText' => 'Monthly Expenses'],
            ['name' => 'Salary - Expenses', 'total' => '800.00', 'helpText' => 'Free Money for Investments'],
        ]], $this->responseJson());
    }

    public function testSummaryTreatsMissingSalaryAsZero(): void
    {
        $admin = $this->admin();
        $this->createExpense($this->createCategory($admin), 'Groceries', '200.00');
        $this->loginAs($admin);

        $this->getJson('/api/expenses/summary');

        self::assertResponseIsSuccessful();
        self::assertSame(['summary' => [
            ['name' => 'Salary', 'total' => '0', 'helpText' => 'Monthly Salary'],
            ['name' => 'All Expenses', 'total' => '200.00', 'helpText' => 'Monthly Expenses'],
            ['name' => 'Salary - Expenses', 'total' => '-200.00', 'helpText' => 'Free Money for Investments'],
        ]], $this->responseJson());
    }
}
