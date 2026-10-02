<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\BrokerSync;

use App\Investments\Application\BrokerSync\SyncBrokerAccountCommand;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\Client\ExternalInstrument;
use App\Investments\Domain\BrokerSync\Client\ExternalPosition;
use App\Investments\Domain\BrokerSync\Client\ExternalPositions;
use App\Investments\Domain\BrokerSync\InstrumentKind;
use App\Investments\Domain\BrokerSync\SyncStatus;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Securities\ShareTypeEnum;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Instruments\ShareSplit;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\Investment;
use App\Investments\Domain\Operations\RecordSource;
use App\Shared\Domain\Bus\SyncCommandBusInterface;
use App\Tests\Investments\BrokerSync\CreatesBrokerLinks;
use App\Tests\Investments\BrokerSync\Operations;
use App\Tests\Support\InteractsWithDatabase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class SyncBrokerAccountCommandHandlerTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use CreatesBrokerLinks;
    use InteractsWithDatabase;

    public function testRebuildsAccountFromBrokerOperations(): void
    {
        static::mockTime('2026-10-02 12:00:00');
        $account = $this->createAccount($this->admin());
        $this->linkAccount($account);
        $sber = $this->createShare('SBER', '320');
        $this->createManualRecords($account);
        $this->brokerClient()->operations = [
            Operations::deposit('1', '2024-04-04 21:37:12', '10000'),
            Operations::buy('2', '2024-04-05 07:00:00', 'SBER', 10, '300'),
            Operations::fee('3', '2024-04-05 07:00:01', '-3', parentId: '2'),
            Operations::sell('4', '2024-05-06 08:00:00', 'SBER', 4, '310', '1.24'),
            Operations::buy('5', '2024-05-07 08:00:00', 'TMON@', 5, '150', classCode: 'SPBRU', kind: InstrumentKind::Etf),
            Operations::payout('6', BrokerOperationType::Dividend, '2024-07-01 21:30:00', 'SBER', '50'),
            Operations::payout('7', BrokerOperationType::DividendTax, '2024-07-01 21:30:00', 'SBER', '-6.5'),
        ];
        $this->brokerClient()->instruments = [
            Operations::uid('TMON@') => new ExternalInstrument(Operations::uid('TMON@'), 'TMON@', 'SPBRU', 'Money market', InstrumentKind::Etf, 'RUB', 1, 'RU000A106DL2', null),
        ];
        $this->brokerClient()->positions = new ExternalPositions(['RUB' => '7530.500000000'], [
            new ExternalPosition(Operations::uid('SBER'), 'SBER', InstrumentKind::Share, 6),
            new ExternalPosition(Operations::uid('TMON@'), 'TMON@', InstrumentKind::Etf, 5),
        ]);

        $this->sync($account);

        $deals = $this->findFreshBy(Deal::class, ['account' => $account->getId()]);
        self::assertSame([
            ['2', 'SBER', 'MOEX', DealStatus::Closed, DealType::Long, 4, '300.0000', '310.0000', '1.2000', '1.2400', '2024-04-05 07:00:00', '2024-05-06 08:00:00'],
            ['2#1', 'SBER', 'MOEX', DealStatus::Active, DealType::Long, 6, '300.0000', '0.0000', '1.8000', null, '2024-04-05 07:00:00', null],
            ['5', 'TMON@', 'SPB', DealStatus::Active, DealType::Long, 5, '150.0000', '0.0000', '0.0000', null, '2024-05-07 08:00:00', null],
        ], array_map(static fn (Deal $deal) => [
            $deal->getExternalId(),
            $deal->getTicker(),
            $deal->getStockMarket(),
            $deal->getStatus(),
            $deal->getType(),
            $deal->getQuantity(),
            $deal->getBuyPrice(),
            $deal->getSellPrice(),
            $deal->getBuyCommission(),
            $deal->getSellCommission(),
            $deal->createdAt()->format('Y-m-d H:i:s'),
            $deal->getClosingDate()?->format('Y-m-d H:i:s'),
        ], $deals));
        self::assertSame($sber->getId(), $deals[0]->getShare()?->getId());

        $sber = $this->findFresh(Share::class, $sber->getId());
        self::assertSame(Operations::uid('SBER'), $sber?->getTUid());
        $etf = $this->findFreshBy(Share::class, ['ticker' => 'TMON@'])[0];
        self::assertSame(Operations::uid('TMON@'), $etf->getTUid());
        self::assertSame('SPB', $etf->getStockMarket());
        self::assertSame('Money market', $etf->getName());

        $dividends = $this->findFreshBy(Dividend::class, ['account' => $account->getId()]);
        self::assertSame(
            [['6', 'SBER', '43.5000', '6.5000', '2024-07-02']],
            array_map(static fn (Dividend $dividend) => [$dividend->getExternalId(), $dividend->getTicker(), $dividend->getAmount(), $dividend->getTax(), $dividend->getDate()?->format('Y-m-d')], $dividends),
        );

        $investments = $this->findFreshBy(Investment::class, ['account' => $account->getId()]);
        self::assertSame(
            [['1', '10000.00', '2024-04-05']],
            array_map(static fn (Investment $investment) => [$investment->getExternalId(), $investment->getSum(), $investment->getDate()?->format('Y-m-d')], $investments),
        );

        $account = $this->findFresh(Account::class, $account->getId());
        self::assertSame('7530.5000', $account?->getBalance());
        $link = $this->findFreshBy(BrokerAccountLink::class, ['account' => $account?->getId()])[0];
        self::assertSame(SyncStatus::Success, $link->getLastSyncStatus());
        self::assertSame([], $link->getLastSyncDiscrepancies());
    }

    public function testKeepsDealsAndTargetPricesBetweenSyncs(): void
    {
        static::mockTime('2026-10-02 12:00:00');
        $account = $this->createAccount($this->admin());
        $this->linkAccount($account);
        $this->createShare('SBER', '320');
        $this->brokerClient()->operations = [Operations::buy('1', '2026-09-01 07:00:00', 'SBER', 10, '300')];
        $this->sync($account);
        $deal = $this->findFreshBy(Deal::class, ['account' => $account->getId()])[0];
        $deal->setTargetPrice('400');
        $this->persist($deal);
        $this->brokerClient()->operations[] = Operations::sell('2', '2026-10-01 07:00:00', 'SBER', 3, '330');

        $this->sync($account);

        $deals = $this->findFreshBy(Deal::class, ['account' => $account->getId()]);
        self::assertSame(
            [[$deal->getId(), '1', DealStatus::Closed, 3], [$deals[1]->getId(), '1#1', DealStatus::Active, 7]],
            array_map(static fn (Deal $deal) => [$deal->getId(), $deal->getExternalId(), $deal->getStatus(), $deal->getQuantity()], $deals),
        );
        self::assertSame('400.0000', $deals[1]->getTargetPrice());
    }

    public function testAppliesShareSplit(): void
    {
        static::mockTime('2026-10-02 12:00:00');
        $account = $this->createAccount($this->admin());
        $this->linkAccount($account);
        $this->createShare('T', '260');
        $this->persist(new ShareSplit('T', 'MOEX', new \DateTimeImmutable('2026-04-17'), 1, 10));
        $this->brokerClient()->operations = [Operations::buy('1', '2026-03-12 08:56:22', 'T', 4, '3376')];
        $this->brokerClient()->positions = new ExternalPositions(['RUB' => '-13504'], [
            new ExternalPosition(Operations::uid('T'), 'T', InstrumentKind::Share, 40),
        ]);

        $this->sync($account);

        $deal = $this->findFreshBy(Deal::class, ['account' => $account->getId()])[0];
        self::assertSame(40, $deal->getQuantity());
        self::assertSame('337.6000', $deal->getBuyPrice());
        $link = $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()])[0];
        self::assertSame(SyncStatus::Success, $link->getLastSyncStatus());
    }

    public function testReportsPositionsThatDifferFromBroker(): void
    {
        $account = $this->createAccount($this->admin());
        $this->linkAccount($account);
        $this->createShare('SBER', '320');
        $this->brokerClient()->operations = [Operations::buy('1', '2026-09-01 07:00:00', 'SBER', 10, '300')];
        $this->brokerClient()->positions = new ExternalPositions(['RUB' => '0'], [
            new ExternalPosition(Operations::uid('SBER'), 'SBER', InstrumentKind::Share, 12),
        ]);

        $this->sync($account);

        $link = $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()])[0];
        self::assertSame(SyncStatus::Warning, $link->getLastSyncStatus());
        self::assertSame([
            ['name' => 'SBER', 'broker' => '12', 'calculated' => '10'],
            ['name' => 'RUB', 'broker' => '0.00', 'calculated' => '-3000.00'],
        ], $link->getLastSyncDiscrepancies());
    }

    public function testKeepsBondPriceInPercentOfNominal(): void
    {
        $account = $this->createAccount($this->admin());
        $this->linkAccount($account);
        $this->brokerClient()->operations = [
            Operations::buy('1', '2024-09-13 13:07:14', 'SU26238RMFS4', 34, '524.01', kind: InstrumentKind::Bond, classCode: 'TQOB', accruedInterest: '681.02'),
        ];
        $this->brokerClient()->instruments = [
            Operations::uid('SU26238RMFS4') => new ExternalInstrument(Operations::uid('SU26238RMFS4'), 'SU26238RMFS4', 'TQOB', 'OFZ 26238', InstrumentKind::Bond, 'RUB', 1, null, '1000.000000000'),
        ];

        $this->sync($account);

        $deal = $this->findFreshBy(Deal::class, ['account' => $account->getId()])[0];
        self::assertSame('52.4010', $deal->getBuyPrice());
        self::assertSame('1000.000000000', $deal->getBond()?->getLotSize() !== null ? bcadd($deal->getBond()->getLotSize(), '0', 9) : null);
        self::assertNotNull($this->findFreshBy(Bond::class, ['ticker' => 'SU26238RMFS4'])[0]->getTUid());
    }

    public function testSkipsDealsOfInstrumentUnknownEverywhere(): void
    {
        $account = $this->createAccount($this->admin());
        $this->linkAccount($account);
        $this->brokerClient()->operations = [Operations::buy('1', '2026-09-01 07:00:00', 'XXXX', 1, '10')];

        $this->sync($account);

        self::assertSame([], $this->findFreshBy(Deal::class, ['account' => $account->getId()]));
        $link = $this->findFreshBy(BrokerAccountLink::class, ['account' => $account->getId()])[0];
        self::assertSame(SyncStatus::Warning, $link->getLastSyncStatus());
        self::assertStringContainsString('XXXX: the instrument is unknown', (string) $link->getLastSyncMessage());
    }

    private function sync(Account $account): void
    {
        /** @var SyncCommandBusInterface $bus */
        $bus = static::getContainer()->get(SyncCommandBusInterface::class);
        $this->entityManager()->clear();
        $bus->dispatch(new SyncBrokerAccountCommand((int) $account->getId()));
    }

    private function createShare(string $ticker, string $price): Share
    {
        $share = new Share($ticker, $ticker . ' name', 'MOEX', 'RUB', $price, ShareTypeEnum::Stock->value);
        $this->persist($share);

        return $share;
    }

    private function createManualRecords(Account $account): void
    {
        $owner = $this->admin();
        $deal = new Deal($owner, $account, 'GAZP', 'MOEX', DealStatus::Active, DealType::Long, 10, '150');
        $investment = new Investment('60000', new \DateTimeImmutable('2024-03-12'), $account, (int) $owner->getId());
        $this->persist($deal, $investment);
        self::assertSame(RecordSource::Manual, $deal->getSource());
    }
}
