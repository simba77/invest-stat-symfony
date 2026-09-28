<?php

declare(strict_types=1);

namespace App\Tests\Deposits\Application\Controller;

use App\Deposits\Domain\Deposit;
use App\Deposits\Domain\DepositAccount;
use App\Tests\Deposits\CreatesDeposits;
use App\Tests\Support\ApiTestCase;

final class DepositAccountsControllerTest extends ApiTestCase
{
    use CreatesDeposits;

    /**
     * @dataProvider protectedEndpoints
     */
    public function testRejectsAnonymousRequests(string $method, string $uri): void
    {
        $this->client->jsonRequest($method, $uri, ['name' => 'Bank']);

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function protectedEndpoints(): iterable
    {
        yield 'list' => ['GET', '/api/deposits/accounts'];
        yield 'create' => ['POST', '/api/deposits/accounts/create'];
        yield 'form' => ['GET', '/api/deposits/accounts/get-form/1'];
        yield 'update' => ['POST', '/api/deposits/accounts/update/1'];
        yield 'delete' => ['POST', '/api/deposits/accounts/delete/1'];
    }

    public function testIndexListsOwnAccounts(): void
    {
        $admin = $this->admin();
        $bank = $this->createAccount($admin, 'Bank');
        $broker = $this->createAccount($admin, 'Broker');
        $this->createAccount($this->otherUser(), 'Not mine');
        $this->loginAs($admin);

        $this->getJson('/api/deposits/accounts');

        self::assertResponseIsSuccessful();
        self::assertSame(['items' => [
            ['id' => $bank->getId(), 'name' => 'Bank'],
            ['id' => $broker->getId(), 'name' => 'Broker'],
        ]], $this->responseJson());
    }

    public function testCreateAddsAccountForCurrentUser(): void
    {
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/deposits/accounts/create', ['name' => 'Savings']);

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        $accounts = $this->findFreshBy(DepositAccount::class, ['user' => $admin->getId()]);
        self::assertCount(1, $accounts);
        self::assertSame('Savings', $accounts[0]->getName());
    }

    /**
     * @dataProvider invalidNames
     */
    public function testCreateRejectsInvalidName(string $name): void
    {
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/deposits/accounts/create', ['name' => $name]);

        $this->assertViolatedFields(['name']);
        self::assertSame([], $this->findFreshBy(DepositAccount::class, ['user' => $admin->getId()]));
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

    public function testFormReturnsAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, 'Bank');
        $this->loginAs($admin);

        $this->getJson('/api/deposits/accounts/get-form/' . $account->getId());

        self::assertResponseIsSuccessful();
        self::assertSame(['id' => $account->getId(), 'name' => 'Bank'], $this->responseJson());
    }

    public function testFormHidesOtherUsersAccount(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->loginAs($this->admin());

        $this->getJson('/api/deposits/accounts/get-form/' . $account->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdateRenamesAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, 'Bank');
        $this->loginAs($admin);

        $this->postJson('/api/deposits/accounts/update/' . $account->getId(), ['name' => 'Savings']);

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        self::assertSame('Savings', $this->findFresh(DepositAccount::class, $account->getId())?->getName());
    }

    public function testUpdateRejectsInvalidName(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, 'Bank');
        $this->loginAs($admin);

        $this->postJson('/api/deposits/accounts/update/' . $account->getId(), ['name' => 'ab']);

        $this->assertViolatedFields(['name']);
        self::assertSame('Bank', $this->findFresh(DepositAccount::class, $account->getId())?->getName());
    }

    public function testUpdateLeavesOtherUsersAccountUntouched(): void
    {
        $account = $this->createAccount($this->otherUser(), 'Bank');
        $this->loginAs($this->admin());

        $this->postJson('/api/deposits/accounts/update/' . $account->getId(), ['name' => 'Renamed']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame('Bank', $this->findFresh(DepositAccount::class, $account->getId())?->getName());
    }

    public function testDeleteRemovesAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->postJson('/api/deposits/accounts/delete/' . $account->getId());

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        self::assertNull($this->findFresh(DepositAccount::class, $account->getId()));
    }

    public function testDeleteAccountWithDeposits(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $deposit = $this->createDeposit($account);
        $this->loginAs($admin);

        $this->postJson('/api/deposits/accounts/delete/' . $account->getId());

        self::assertResponseIsSuccessful();
        self::assertNull($this->findFresh(DepositAccount::class, $account->getId()));
        self::assertNull($this->findFresh(Deposit::class, $deposit->getId()));
    }

    public function testDeleteLeavesOtherUsersAccountUntouched(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->loginAs($this->admin());

        $this->postJson('/api/deposits/accounts/delete/' . $account->getId());

        self::assertResponseStatusCodeSame(404);
        self::assertNotNull($this->findFresh(DepositAccount::class, $account->getId()));
    }
}
