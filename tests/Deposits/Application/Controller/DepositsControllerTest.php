<?php

declare(strict_types=1);

namespace App\Tests\Deposits\Application\Controller;

use App\Deposits\Domain\Deposit;
use App\Tests\Deposits\CreatesDeposits;
use App\Tests\Support\ApiTestCase;

final class DepositsControllerTest extends ApiTestCase
{
    use CreatesDeposits;

    /**
     * @dataProvider protectedEndpoints
     */
    public function testRejectsAnonymousRequests(string $method, string $uri): void
    {
        $this->client->jsonRequest($method, $uri, ['accountId' => 1, 'sum' => '100', 'type' => 1, 'date' => '2025-01-15']);

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function protectedEndpoints(): iterable
    {
        yield 'list' => ['GET', '/api/deposits'];
        yield 'stats' => ['GET', '/api/deposits/stats'];
        yield 'create' => ['POST', '/api/deposits/create'];
        yield 'form' => ['GET', '/api/deposits/get-form/1'];
        yield 'update' => ['POST', '/api/deposits/update/1'];
        yield 'delete' => ['POST', '/api/deposits/delete/1'];
    }

    public function testIndexListsOwnDepositsNewestFirst(): void
    {
        $admin = $this->admin();
        $bank = $this->createAccount($admin, 'Bank');
        $first = $this->createDeposit($bank, '1000.00', self::DEPOSIT, '2025-01-10');
        $percent = $this->createDeposit($bank, '15.50', self::PERCENT, '2025-02-01');
        $withdrawal = $this->createDeposit($bank, '-200.00', self::DEPOSIT, '2025-02-01');
        $this->createDeposit($this->createAccount($this->otherUser()), '999.00');
        $this->loginAs($admin);

        $this->getJson('/api/deposits');

        self::assertResponseIsSuccessful();
        self::assertSame([
            'items'      => [
                ['id' => $withdrawal->getId(), 'date' => '01.02.2025', 'sum' => '-200.00', 'typeName' => 'Deposit', 'accountName' => 'Bank'],
                ['id' => $percent->getId(), 'date' => '01.02.2025', 'sum' => '15.50', 'typeName' => 'Percent', 'accountName' => 'Bank'],
                ['id' => $first->getId(), 'date' => '10.01.2025', 'sum' => '1000.00', 'typeName' => 'Deposit', 'accountName' => 'Bank'],
            ],
            'pagination' => ['page' => 1, 'perPage' => 20, 'totalItems' => 3, 'totalPages' => 1, 'hasPrev' => false, 'hasNext' => false],
        ], $this->responseJson());
    }

    public function testIndexPaginates(): void
    {
        $admin = $this->admin();
        $bank = $this->createAccount($admin);
        $oldest = $this->createDeposit($bank, date: '2025-01-01');
        $this->createDeposit($bank, date: '2025-02-01');
        $this->createDeposit($bank, date: '2025-03-01');
        $this->loginAs($admin);

        $this->getJson('/api/deposits?page=2&perPage=2');

        self::assertResponseIsSuccessful();
        /** @var array{items: list<array{id: int}>, pagination: array<string, mixed>} $body */
        $body = $this->responseJson();
        self::assertSame([$oldest->getId()], array_column($body['items'], 'id'));
        self::assertSame(
            ['page' => 2, 'perPage' => 2, 'totalItems' => 3, 'totalPages' => 2, 'hasPrev' => true, 'hasNext' => false],
            $body['pagination'],
        );
    }

    public function testIndexServesLastPageWhenPageIsOutOfRange(): void
    {
        $admin = $this->admin();
        $bank = $this->createAccount($admin);
        $oldest = $this->createDeposit($bank, date: '2025-01-01');
        $this->createDeposit($bank, date: '2025-02-01');
        $this->createDeposit($bank, date: '2025-03-01');
        $this->loginAs($admin);

        $this->getJson('/api/deposits?page=5&perPage=2');

        self::assertResponseIsSuccessful();
        /** @var array{items: list<array{id: int}>, pagination: array{page: int}} $body */
        $body = $this->responseJson();
        self::assertSame(2, $body['pagination']['page']);
        self::assertSame([$oldest->getId()], array_column($body['items'], 'id'));
    }

    /**
     * @dataProvider pageSizes
     */
    public function testIndexNormalizesPageSize(int $requested, int $expected): void
    {
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->getJson('/api/deposits?perPage=' . $requested);

        self::assertResponseIsSuccessful();
        self::assertSame($expected, $this->responseJson()['pagination']['perPage']);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function pageSizes(): iterable
    {
        yield 'above the maximum' => [1000, 100];
        yield 'zero' => [0, 20];
        yield 'negative' => [-5, 20];
    }

    public function testCreateAddsDeposit(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->postJson('/api/deposits/create', [
            'accountId' => $account->getId(),
            'sum'       => '1500.50',
            'type'      => self::PERCENT,
            'date'      => '2025-03-15',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        $deposits = $this->findFreshBy(Deposit::class, ['user' => $admin->getId()]);
        self::assertCount(1, $deposits);
        self::assertSame('1500.50', $deposits[0]->getSum());
        self::assertSame(self::PERCENT, $deposits[0]->getType());
        self::assertSame('2025-03-15', $deposits[0]->getDate()?->format('Y-m-d'));
        self::assertSame($account->getId(), $deposits[0]->getDepositAccount()?->getId());
    }

    public function testCreateRejectsOtherUsersAccount(): void
    {
        $account = $this->createAccount($this->otherUser());
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/deposits/create', $this->payload($account->getId()));

        self::assertResponseStatusCodeSame(404);
        self::assertSame([], $this->findFreshBy(Deposit::class, ['user' => $admin->getId()]));
    }

    /**
     * @dataProvider invalidPayloads
     *
     * @param array<string, mixed> $overrides
     * @param list<string> $violatedFields
     */
    public function testCreateRejectsInvalidPayload(array $overrides, array $violatedFields): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->postJson('/api/deposits/create', array_replace($this->payload($account->getId()), $overrides));

        $this->assertViolatedFields($violatedFields);
        self::assertSame([], $this->findFreshBy(Deposit::class, ['user' => $admin->getId()]));
    }

    /**
     * What the deposit form can send: an empty number input becomes null, no account chosen is ''.
     *
     * @return iterable<string, array{array<string, mixed>, list<string>}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'no account chosen' => [['accountId' => ''], ['accountId']];
        yield 'empty sum' => [['sum' => null], ['sum']];
        yield 'sum is not a number' => [['sum' => 'ten'], ['sum']];
        yield 'unknown type' => [['type' => 3], ['type']];
        yield 'empty date' => [['date' => ''], ['date']];
        yield 'date in another format' => [['date' => '15.01.2025'], ['date']];
        yield 'impossible date' => [['date' => '2025-02-30'], ['date']];
    }

    public function testFormReturnsDeposit(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $deposit = $this->createDeposit($account, '250.75', self::PERCENT, '2025-04-30');
        $this->loginAs($admin);

        $this->getJson('/api/deposits/get-form/' . $deposit->getId());

        self::assertResponseIsSuccessful();
        self::assertSame([
            'id'        => $deposit->getId(),
            'sum'       => '250.75',
            'type'      => self::PERCENT,
            'accountId' => $account->getId(),
            'date'      => '2025-04-30',
        ], $this->responseJson());
    }

    public function testFormHidesOtherUsersDeposit(): void
    {
        $deposit = $this->createDeposit($this->createAccount($this->otherUser()));
        $this->loginAs($this->admin());

        $this->getJson('/api/deposits/get-form/' . $deposit->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdateChangesDeposit(): void
    {
        $admin = $this->admin();
        $deposit = $this->createDeposit($this->createAccount($admin, 'Bank'), '100.00', self::DEPOSIT, '2025-01-15');
        $broker = $this->createAccount($admin, 'Broker');
        $this->loginAs($admin);

        $this->postJson('/api/deposits/update/' . $deposit->getId(), [
            'accountId' => $broker->getId(),
            'sum'       => '42.10',
            'type'      => self::PERCENT,
            'date'      => '2025-02-20',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        $updated = $this->findFresh(Deposit::class, $deposit->getId());
        self::assertSame('42.10', $updated?->getSum());
        self::assertSame(self::PERCENT, $updated?->getType());
        self::assertSame('2025-02-20', $updated?->getDate()?->format('Y-m-d'));
        self::assertSame($broker->getId(), $updated?->getDepositAccount()?->getId());
    }

    public function testUpdateRejectsInvalidPayload(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $deposit = $this->createDeposit($account, '100.00');
        $this->loginAs($admin);

        $this->postJson('/api/deposits/update/' . $deposit->getId(), array_replace($this->payload($account->getId()), ['sum' => 'ten']));

        $this->assertViolatedFields(['sum']);
        self::assertSame('100.00', $this->findFresh(Deposit::class, $deposit->getId())?->getSum());
    }

    public function testUpdateRejectsMovingToOtherUsersAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $deposit = $this->createDeposit($account);
        $foreignAccount = $this->createAccount($this->otherUser());
        $this->loginAs($admin);

        $this->postJson('/api/deposits/update/' . $deposit->getId(), $this->payload($foreignAccount->getId()));

        self::assertResponseStatusCodeSame(404);
        self::assertSame($account->getId(), $this->findFresh(Deposit::class, $deposit->getId())?->getDepositAccount()?->getId());
    }

    public function testUpdateLeavesOtherUsersDepositUntouched(): void
    {
        $account = $this->createAccount($this->otherUser());
        $deposit = $this->createDeposit($account, '100.00');
        $this->loginAs($this->admin());

        $this->postJson('/api/deposits/update/' . $deposit->getId(), array_replace($this->payload($account->getId()), ['sum' => '1.00']));

        self::assertResponseStatusCodeSame(404);
        self::assertSame('100.00', $this->findFresh(Deposit::class, $deposit->getId())?->getSum());
    }

    public function testDeleteRemovesDeposit(): void
    {
        $admin = $this->admin();
        $deposit = $this->createDeposit($this->createAccount($admin));
        $this->loginAs($admin);

        $this->postJson('/api/deposits/delete/' . $deposit->getId());

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        self::assertNull($this->findFresh(Deposit::class, $deposit->getId()));
    }

    public function testDeleteLeavesOtherUsersDepositUntouched(): void
    {
        $deposit = $this->createDeposit($this->createAccount($this->otherUser()));
        $this->loginAs($this->admin());

        $this->postJson('/api/deposits/delete/' . $deposit->getId());

        self::assertResponseStatusCodeSame(404);
        self::assertNotNull($this->findFresh(Deposit::class, $deposit->getId()));
    }

    public function testStatsAreEmptyWithoutAccounts(): void
    {
        $this->loginAs($this->admin());

        $this->getJson('/api/deposits/stats');

        self::assertResponseIsSuccessful();
        self::assertSame([
            'summary'      => ['balance' => '0', 'profit' => '0', 'profitPercent' => '0', 'annualizedPercent' => '0'],
            'monthlyStats' => [],
            'accounts'     => [],
        ], $this->responseJson());
    }

    public function testStatsGroupOwnDepositsAndProfitByMonth(): void
    {
        $admin = $this->admin();
        $bank = $this->createAccount($admin);
        $this->createDeposit($bank, '1000.00', self::DEPOSIT, '2025-01-10');
        $this->createDeposit($bank, '500.00', self::DEPOSIT, '2025-01-25');
        $this->createDeposit($bank, '12.50', self::PERCENT, '2025-01-31');
        $this->createDeposit($bank, '7.25', self::PERCENT, '2025-03-31');
        $this->createDeposit($this->createAccount($this->otherUser()), '999.00', self::DEPOSIT, '2025-01-15');
        $this->loginAs($admin);

        $this->getJson('/api/deposits/stats');

        self::assertResponseIsSuccessful();
        self::assertSame([
            ['month' => '2025.01', 'deposits' => '1500.00', 'profit' => '12.50'],
            ['month' => '2025.03', 'deposits' => '0.00', 'profit' => '7.25'],
        ], $this->responseJson()['monthlyStats']);
    }

    /**
     * Profit percent = profit / money put in; annualized = profit percent * 365 / days the
     * balance was positive. Both are truncated to 2 decimals.
     */
    public function testStatsCalculateProfitabilityPerAccountAndInTotal(): void
    {
        $admin = $this->admin();
        // Positive for the last 200 days: 1000 / 100000 = 1.00 %, 1.00 * 365 / 200 = 1.82 %
        $savings = $this->createAccount($admin, 'Savings');
        $this->createDeposit($savings, '100000.00', self::DEPOSIT, self::daysAgo(200));
        $this->createDeposit($savings, '1000.00', self::PERCENT, self::daysAgo(100));
        $this->createDeposit($savings, '-20000.00', self::DEPOSIT, self::daysAgo(50));
        // Positive for 50 days, emptied, then positive for the last 100 days: 150 active days,
        // 500 / 60000 = 0.83 %, 0.83 * 365 / 150 = 2.01 %
        $broker = $this->createAccount($admin, 'Broker');
        $this->createDeposit($broker, '50000.00', self::DEPOSIT, self::daysAgo(300));
        $this->createDeposit($broker, '-50000.00', self::DEPOSIT, self::daysAgo(250));
        $this->createDeposit($broker, '10000.00', self::DEPOSIT, self::daysAgo(100));
        $this->createDeposit($broker, '500.00', self::PERCENT, self::daysAgo(10));
        $this->createDeposit($this->createAccount($this->otherUser()), '999.00', self::PERCENT, self::daysAgo(10));
        $this->loginAs($admin);

        $this->getJson('/api/deposits/stats');

        self::assertResponseIsSuccessful();
        /** @var array{summary: array<string, string>, accounts: list<array<string, mixed>>} $stats */
        $stats = $this->responseJson();
        // 1500 / 160000 = 0.93 %; average of 200 and 150 active days is 175: 0.93 * 365 / 175 = 1.93 %
        self::assertSame(
            ['balance' => '91500.00', 'profit' => '1500.00', 'profitPercent' => '0.93', 'annualizedPercent' => '1.93'],
            $stats['summary'],
        );
        self::assertSame([
            [
                'id'                => $savings->getId(),
                'name'              => 'Savings',
                'balance'           => '81000.00',
                'profit'            => '1000.00',
                'grossInvested'     => '100000.00',
                'profitPercent'     => '1.00',
                'annualizedPercent' => '1.82',
            ],
            [
                'id'                => $broker->getId(),
                'name'              => 'Broker',
                'balance'           => '10500.00',
                'profit'            => '500.00',
                'grossInvested'     => '60000.00',
                'profitPercent'     => '0.83',
                'annualizedPercent' => '2.01',
            ],
        ], $stats['accounts']);
    }

    public function testStatsListAccountWithoutDepositsWithZeros(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, 'Empty');
        $this->loginAs($admin);

        $this->getJson('/api/deposits/stats');

        self::assertResponseIsSuccessful();
        self::assertSame([[
            'id'                => $account->getId(),
            'name'              => 'Empty',
            'balance'           => '0.00',
            'profit'            => '0.00',
            'grossInvested'     => '0.00',
            'profitPercent'     => '0',
            'annualizedPercent' => '0',
        ]], $this->responseJson()['accounts']);
    }

    /**
     * @return array{accountId: ?int, sum: string, type: int, date: string}
     */
    private function payload(?int $accountId): array
    {
        return ['accountId' => $accountId, 'sum' => '100.00', 'type' => self::DEPOSIT, 'date' => '2025-01-15'];
    }
}
