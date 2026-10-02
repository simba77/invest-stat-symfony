<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerOperation;
use App\Investments\Domain\BrokerSync\Client\ExternalAccount;
use App\Investments\Domain\BrokerSync\Client\ExternalPositions;
use App\Investments\Domain\BrokerSync\FeeAllocation;
use App\Tests\Investments\BrokerSync\CreatesBrokerLinks;
use App\Tests\Investments\BrokerSync\Operations;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class BrokerSyncControllerTest extends ApiTestCase
{
    use ClockSensitiveTrait;
    use CreatesBrokerLinks;

    /**
     * @dataProvider protectedEndpoints
     */
    public function testRejectsAnonymousRequests(string $method, string $uri): void
    {
        $this->client->jsonRequest($method, $uri, ['provider' => 'tinvest']);

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function protectedEndpoints(): iterable
    {
        yield 'show' => ['GET', '/api/accounts/1/broker-sync'];
        yield 'save' => ['POST', '/api/accounts/1/broker-sync'];
        yield 'run' => ['POST', '/api/accounts/1/broker-sync/run'];
        yield 'delete' => ['POST', '/api/accounts/1/broker-sync/delete'];
        yield 'external accounts' => ['POST', '/api/accounts/1/broker-sync/external-accounts'];
    }

    public function testShowReturnsOptionsForUnlinkedAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->getJson('/api/accounts/' . $account->getId() . '/broker-sync');

        self::assertResponseIsSuccessful();
        self::assertSame([
            'settings'       => null,
            'providers'      => [['code' => 'tinvest', 'name' => 'T-Bank']],
            'feeAllocations' => [
                ['code' => 'per_operation', 'name' => 'Per trade'],
                ['code' => 'daily_pro_rata', 'name' => 'Daily total, split by turnover'],
            ],
        ], $this->responseJson());
    }

    public function testShowReturnsSettingsWithoutToken(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->linkAccount($account, token: 'secret-token');
        $this->loginAs($admin);

        $this->getJson('/api/accounts/' . $account->getId() . '/broker-sync');

        self::assertResponseIsSuccessful();
        /** @var array{settings: array<string, mixed>} $body */
        $body = $this->responseJson();
        self::assertSame([
            'provider'            => 'tinvest',
            'externalAccountId'   => '2000',
            'externalAccountName' => 'Broker account',
            'feeAllocation'       => 'per_operation',
            'enabled'             => true,
            'lastSyncedAt'        => null,
            'lastSyncStatus'      => null,
            'lastSyncMessage'     => null,
            'discrepancies'       => [],
        ], $body['settings']);
        self::assertStringNotContainsString('secret-token', (string) $this->client->getResponse()->getContent());
    }

    public function testShowHidesAccountOfOtherUser(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->loginAs($this->admin());

        $this->getJson('/api/accounts/' . $account->getId() . '/broker-sync');

        self::assertResponseStatusCodeSame(404);
    }

    public function testSaveLinksAccountAndEncryptsToken(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->brokerHasAccounts(
            new ExternalAccount('1000', 'Main', new \DateTimeImmutable('2021-08-23 00:00:00'), true),
            new ExternalAccount('2000', 'Autofollow', new \DateTimeImmutable('2024-03-10 00:00:00'), true),
        );
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync', [
            'provider'          => 'tinvest',
            'token'             => ' new-token ',
            'externalAccountId' => '2000',
            'feeAllocation'     => 'daily_pro_rata',
            'enabled'           => true,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        self::assertSame(['new-token'], $this->brokerClient()->receivedTokens);
        $links = $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()]);
        self::assertCount(1, $links);
        self::assertSame('Autofollow', $links[0]->getExternalAccountName());
        self::assertSame('2024-03-10', $links[0]->getExternalAccountOpenedAt()?->format('Y-m-d'));
        self::assertSame(FeeAllocation::DailyProRata, $links[0]->getFeeAllocation());
        self::assertNotSame('new-token', $links[0]->getEncryptedToken());
        self::assertSame('new-token', $this->tokenCipher()->decrypt($links[0]->getEncryptedToken()));
    }

    public function testSaveKeepsStoredTokenWhenNoneGiven(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->linkAccount($account, token: 'stored-token');
        $this->brokerHasAccounts(new ExternalAccount('2000', 'Renamed', null, true));
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync', [
            'provider'          => 'tinvest',
            'token'             => '',
            'externalAccountId' => '2000',
            'feeAllocation'     => 'per_operation',
            'enabled'           => false,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['stored-token'], $this->brokerClient()->receivedTokens);
        $link = $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()])[0];
        self::assertFalse($link->isEnabled());
        self::assertSame('Renamed', $link->getExternalAccountName());
        self::assertSame('stored-token', $this->tokenCipher()->decrypt($link->getEncryptedToken()));
    }

    public function testSaveRequiresTokenForNewLink(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync', [
            'provider'          => 'tinvest',
            'externalAccountId' => '2000',
            'feeAllocation'     => 'per_operation',
        ]);

        $this->assertViolatedFields(['token']);
        self::assertSame([], $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()]));
    }

    public function testSaveRejectsInvalidPayload(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync', [
            'provider'          => 'unknown',
            'token'             => 'token',
            'externalAccountId' => '',
            'feeAllocation'     => 'monthly',
        ]);

        $this->assertViolatedFields(['provider', 'externalAccountId', 'feeAllocation']);
    }

    public function testSaveRejectsAccountTheTokenCannotRead(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->brokerHasAccounts(new ExternalAccount('1000', 'Main', null, true));
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync', [
            'provider'          => 'tinvest',
            'token'             => 'token',
            'externalAccountId' => '2000',
            'feeAllocation'     => 'per_operation',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['message' => 'The broker has no account "2000" for this token'], $this->responseJson());
        self::assertSame([], $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()]));
    }

    public function testSaveLeavesAccountOfOtherUserUnlinked(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->brokerHasAccounts(new ExternalAccount('2000', 'Main', null, true));
        $this->loginAs($this->admin());

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync', [
            'provider'          => 'tinvest',
            'token'             => 'token',
            'externalAccountId' => '2000',
            'feeAllocation'     => 'per_operation',
        ]);

        self::assertResponseStatusCodeSame(404);
        self::assertSame([], $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()]));
    }

    public function testSaveDropsOperationsOfPreviousExternalAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $link = $this->linkAccount($account, externalAccountId: '1000');
        $this->persist(new BrokerOperation($link, Operations::deposit('1', '2026-01-01 10:00:00', '100')));
        $this->brokerHasAccounts(new ExternalAccount('2000', 'Other', null, true));
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync', [
            'provider'          => 'tinvest',
            'externalAccountId' => '2000',
            'feeAllocation'     => 'per_operation',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->findFreshBy(BrokerOperation::class, ['link' => $link->getId()]));
    }

    public function testRunSyncsAccountAndReturnsStatus(): void
    {
        static::mockTime('2026-10-02 12:00:00');
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->linkAccount($account);
        $this->brokerClient()->operations = [Operations::deposit('1', '2026-01-01 10:00:00', '100')];
        $this->brokerClient()->positions = new ExternalPositions(['RUB' => '100'], []);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync/run');

        self::assertResponseIsSuccessful();
        /** @var array{settings: array<string, mixed>} $body */
        $body = $this->responseJson();
        self::assertSame('2026-10-02T12:00:00+00:00', $body['settings']['lastSyncedAt']);
        self::assertSame('success', $body['settings']['lastSyncStatus']);
        self::assertCount(1, $this->findFreshBy(BrokerOperation::class, []));
    }

    public function testRunReportsBrokerFailure(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->linkAccount($account);
        $this->brokerClient()->failure = 'T-Bank rejected the token';
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync/run');

        self::assertResponseStatusCodeSame(502);
        self::assertSame(['message' => 'T-Bank rejected the token'], $this->responseJson());
        $link = $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()])[0];
        self::assertSame('failed', $link->getLastSyncStatus()?->value);
    }

    public function testRunLeavesAccountOfOtherUserAlone(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->linkAccount($account);
        $this->loginAs($this->admin());

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync/run');

        self::assertResponseStatusCodeSame(404);
        self::assertSame([], $this->brokerClient()->operationRequests);
    }

    public function testDeleteUnlinksAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->linkAccount($account);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync/delete');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()]));
    }

    public function testDeleteLeavesLinkOfOtherUser(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->linkAccount($account);
        $this->loginAs($this->admin());

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync/delete');

        self::assertResponseStatusCodeSame(404);
        self::assertCount(1, $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()]));
    }

    public function testExternalAccountsListsAccountsOfGivenToken(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->brokerHasAccounts(
            new ExternalAccount('1000', 'Main', null, true),
            new ExternalAccount('2000', 'Trading', null, false),
        );
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync/external-accounts', [
            'provider' => 'tinvest',
            'token'    => 'token',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['items' => [
            ['id' => '1000', 'name' => 'Main', 'readOnly' => true],
            ['id' => '2000', 'name' => 'Trading', 'readOnly' => false],
        ]], $this->responseJson());
        self::assertSame(['token'], $this->brokerClient()->receivedTokens);
    }

    public function testExternalAccountsUsesStoredToken(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->linkAccount($account, token: 'stored-token');
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync/external-accounts', ['provider' => 'tinvest']);

        self::assertResponseIsSuccessful();
        self::assertSame(['stored-token'], $this->brokerClient()->receivedTokens);
    }

    public function testExternalAccountsRequiresToken(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync/external-accounts', ['provider' => 'tinvest']);

        $this->assertViolatedFields(['token']);
    }

    public function testExternalAccountsReportsBrokerError(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->brokerClient()->failure = 'T-Bank rejected the token';
        $this->loginAs($admin);

        $this->postJson('/api/accounts/' . $account->getId() . '/broker-sync/external-accounts', [
            'provider' => 'tinvest',
            'token'    => 'token',
        ]);

        self::assertResponseStatusCodeSame(502);
        self::assertSame(['message' => 'T-Bank rejected the token'], $this->responseJson());
    }
}
