<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Command;

use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerOperation;
use App\Investments\Domain\BrokerSync\BrokerOperationState;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\SyncStatus;
use App\Tests\Investments\BrokerSync\CreatesBrokerLinks;
use App\Tests\Investments\BrokerSync\Operations;
use App\Tests\Support\InteractsWithDatabase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class BrokerSyncCommandTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use CreatesBrokerLinks;
    use InteractsWithDatabase;

    public function testImportsWholeHistoryOnFirstSync(): void
    {
        static::mockTime('2026-10-02 12:00:00');
        $account = $this->createAccount($this->admin());
        $this->linkAccount($account, token: 'account-token', externalAccountId: '2000');
        $this->brokerClient()->operations = [
            Operations::deposit('1', '2024-03-12 18:50:13', '10000'),
            Operations::buy('2', '2024-03-13 15:04:13', 'SBER', 30, '299.85', '26.99'),
            Operations::fee('3', '2024-03-13 15:04:14', '-26.99', parentId: '2'),
        ];
        $command = $this->command();

        $command->execute(['--account' => (string) $account->getId()]);

        $command->assertCommandIsSuccessful();
        self::assertSame(['account-token'], $this->brokerClient()->receivedTokens);
        $request = $this->brokerClient()->operationRequests[0];
        self::assertSame('2000', $request['account']);
        self::assertSame('2024-03-10 00:00:00', $request['from']->format('Y-m-d H:i:s'));
        self::assertSame('2026-10-02 12:00:00', $request['to']->format('Y-m-d H:i:s'));
        $operations = $this->findFreshBy(BrokerOperation::class, []);
        self::assertSame(['1', '2', '3'], array_map(static fn (BrokerOperation $operation) => $operation->getExternalId(), $operations));
        self::assertSame(BrokerOperationType::Buy, $operations[1]->getType());
        self::assertSame('299.850000000', $operations[1]->getPrice());
        self::assertSame('2', $operations[2]->getParentExternalId());
        $link = $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()])[0];
        self::assertSame(SyncStatus::Success, $link->getLastSyncStatus());
        self::assertSame('2026-10-02 12:00:00', $link->getLastSyncedAt()?->format('Y-m-d H:i:s'));
    }

    public function testRevisesKnownOperationsOnNextSync(): void
    {
        static::mockTime('2026-10-02 12:00:00');
        $account = $this->createAccount($this->admin());
        $link = $this->linkAccount($account);
        $link->markSynced(new \DateTimeImmutable('2026-10-01 12:00:00'), []);
        $this->persist(
            $link,
            new BrokerOperation($link, Operations::buy('1', '2026-09-30 10:00:00', 'SBER', 10, '300', state: BrokerOperationState::InProgress)),
        );
        $this->brokerClient()->operations = [
            Operations::buy('1', '2026-09-30 10:00:00', 'SBER', 10, '300'),
            Operations::sell('2', '2026-10-02 11:00:00', 'SBER', 10, '310'),
        ];
        $command = $this->command();

        $command->execute(['--account' => (string) $account->getId()]);

        $command->assertCommandIsSuccessful();
        self::assertSame('2026-09-24 12:00:00', $this->brokerClient()->operationRequests[0]['from']->format('Y-m-d H:i:s'));
        $operations = $this->findFreshBy(BrokerOperation::class, []);
        self::assertCount(2, $operations);
        self::assertSame(BrokerOperationState::Executed, $operations[0]->getState());
    }

    public function testRecordsFailureAndKeepsLastSyncTime(): void
    {
        static::mockTime('2026-10-02 12:00:00');
        $account = $this->createAccount($this->admin());
        $link = $this->linkAccount($account);
        $link->markSynced(new \DateTimeImmutable('2026-10-01 12:00:00'), []);
        $this->persist($link);
        $this->brokerClient()->failure = 'T-Bank API error 14: unavailable';
        $command = $this->command();

        $command->execute(['--account' => (string) $account->getId()]);

        self::assertSame(Command::FAILURE, $command->getStatusCode());
        self::assertStringContainsString('T-Bank API error 14: unavailable', $command->getDisplay());
        $link = $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()])[0];
        self::assertSame(SyncStatus::Failed, $link->getLastSyncStatus());
        self::assertSame('T-Bank API error 14: unavailable', $link->getLastSyncMessage());
        self::assertSame('2026-10-01 12:00:00', $link->getLastSyncedAt()?->format('Y-m-d H:i:s'));
    }

    public function testSyncsOnlyEnabledLinksByDefault(): void
    {
        $enabled = $this->createAccount($this->admin(), 'Enabled');
        $this->linkAccount($enabled, externalAccountId: '1000');
        $disabled = $this->createAccount($this->admin(), 'Disabled');
        $link = $this->linkAccount($disabled, externalAccountId: '2000');
        $link->reconfigure($link->getProvider(), '2000', 'Disabled', null, $link->getFeeAllocation(), false);
        $this->persist($link);
        $command = $this->command();

        $command->execute([]);

        $command->assertCommandIsSuccessful();
        self::assertSame(['1000'], array_column($this->brokerClient()->operationRequests, 'account'));
    }

    public function testRefusesToSyncDisabledLink(): void
    {
        $account = $this->createAccount($this->admin());
        $link = $this->linkAccount($account);
        $link->reconfigure($link->getProvider(), '2000', 'Disabled', null, $link->getFeeAllocation(), false);
        $this->persist($link);
        $command = $this->command();

        $command->execute(['--account' => (string) $account->getId()]);

        self::assertSame(Command::FAILURE, $command->getStatusCode());
        self::assertStringContainsString('Sync is turned off', $command->getDisplay());
        self::assertSame([], $this->brokerClient()->operationRequests);
    }

    private function command(): CommandTester
    {
        return new CommandTester((new Application(static::$kernel ?? static::bootKernel()))->find('broker:sync'));
    }
}
