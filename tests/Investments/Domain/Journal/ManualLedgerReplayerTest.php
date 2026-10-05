<?php

declare(strict_types=1);

namespace App\Tests\Investments\Domain\Journal;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\BrokerSync\Ledger\Lot;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Instrument;
use App\Investments\Domain\Instruments\Securities\ShareTypeEnum;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Journal\ManualLedger;
use App\Investments\Domain\Journal\ManualLedgerReplayer;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Journal\ManualOperationType;
use PHPUnit\Framework\TestCase;

final class ManualLedgerReplayerTest extends TestCase
{
    private Account $account;

    private int $nextId = 1;

    #[\Override]
    protected function setUp(): void
    {
        $this->account = new Account(1, 'Manual');
    }

    public function testSaleByQuantityClosesOldestLotAndKeepsTheRestWithItsTarget(): void
    {
        $sber = $this->share('SBER');

        $ledger = $this->replay(
            $this->buy($sber, 10, '250', '2025-06-10', target: '320'),
            $this->close($sber, 4, '310', '2026-03-02'),
        );

        self::assertSame(
            [
                ['op:1', 4, '250', '310', '2026-03-02', null, '320'],
                ['op:1#1', 6, '250', null, null, null, '320'],
            ],
            $this->lots($ledger),
        );
        self::assertSame('2025-06-10', $ledger->lots[1]->getOpenedAt()->format('Y-m-d'));
        self::assertSame(['RUB' => '-1260.0000'], $ledger->cash);
    }

    public function testShortSaleBringsMoneyAndBuyingBackTakesIt(): void
    {
        $gazp = $this->share('GAZP');

        $ledger = $this->replay(
            $this->buy($gazp, 20, '170', '2026-01-12', type: ManualOperationType::Short),
            $this->close($gazp, 20, '160', '2026-02-15', lot: 'op:1'),
        );

        self::assertSame([['op:1', 20, '170', '160', '2026-02-15', null, null]], $this->lots($ledger));
        self::assertSame(['RUB' => '200.0000'], $ledger->cash);
    }

    public function testBondIsPaidByNominalPercentAndAccruedCoupon(): void
    {
        $bond = new Bond('SU26238RMFS4', 'ОФЗ 26238', 'MOEX', 'RUB', '95', lotSize: '1000');
        $this->identify($bond);

        $ledger = $this->replay(
            $this->buy($bond, 2, '98.5', '2025-03-03', accrued: '12.5'),
            $this->close($bond, 2, '99', '2026-03-02', accrued: '20'),
        );

        // -2 × (985 + 12.5) + 2 × (990 + 20)
        self::assertSame(['RUB' => '25.0000'], $ledger->cash);
    }

    public function testFutureBringsItsResultWhenClosed(): void
    {
        $future = new Future('SiH6', 'Si-3.26', 'MOEX', 'RUB', '90000', lotSize: '1', stepPrice: '1');
        $future->setMultiplier('10');
        $this->identify($future);

        $opened = $this->replay($this->buy($future, 1, '85000', '2026-01-25'));
        $this->nextId = 1;
        $closed = $this->replay(
            $this->buy($future, 1, '85000', '2026-01-25'),
            $this->close($future, 1, '88000', '2026-02-01'),
        );

        self::assertSame(['RUB' => '0.0000'], $opened->cash);
        self::assertSame(['RUB' => '30000.0000'], $closed->cash);
    }

    public function testSaleByQuantityPassesBlockedLotsBy(): void
    {
        $sber = $this->share('SBER');

        $ledger = $this->replay(
            $this->buy($sber, 4, '200', '2022-01-20'),
            ManualOperation::block($this->account, new \DateTimeImmutable('2022-03-01'), 'op:1'),
            $this->buy($sber, 10, '250', '2025-06-10'),
            $this->close($sber, 5, '310', '2026-03-02'),
        );

        self::assertSame(
            [
                ['op:1', 4, '200', null, null, true, null],
                ['op:3', 5, '250', '310', '2026-03-02', null, null],
                ['op:3#1', 5, '250', null, null, null, null],
            ],
            $this->lots($ledger),
        );
    }

    public function testUnblockedLotIsSoldAgain(): void
    {
        $sber = $this->share('SBER');

        $ledger = $this->replay(
            $this->buy($sber, 4, '200', '2022-01-20'),
            ManualOperation::block($this->account, new \DateTimeImmutable('2022-03-01'), 'op:1'),
            ManualOperation::block($this->account, new \DateTimeImmutable('2025-01-01'), 'op:1', blocked: false),
            $this->close($sber, 4, '310', '2026-03-02'),
        );

        self::assertSame([['op:1', 4, '200', '310', '2026-03-02', null, null]], $this->lots($ledger));
    }

    public function testCloseEnteredBeforeItsPurchaseWaitsForIt(): void
    {
        $sber = $this->share('SBER');

        $ledger = $this->replay(
            $this->close($sber, 10, '260', '2025-01-15', lot: 'op:2'),
            $this->buy($sber, 10, '200', '2025-02-01'),
        );

        self::assertSame([['op:2', 10, '200', '260', '2025-01-15', null, null]], $this->lots($ledger));
        self::assertSame([], $ledger->warnings);
    }

    public function testCashAdjustmentAndUnknownSecurityOutsideMoex(): void
    {
        $ledger = $this->replay(
            ManualOperation::cashAdjustment($this->account, new \DateTimeImmutable('2025-01-01'), 'RUB', '1000'),
            ManualOperation::open($this->account, ManualOperationType::Buy, new \DateTimeImmutable('2025-02-01'), null, 'UNKNOWN', 'SPB', 4, '50'),
        );

        self::assertSame(['RUB' => '1000.0000', 'USD' => '-200.0000'], $ledger->cash);
    }

    public function testBlockOfCashSetsAsideItsPartWithoutSpendingIt(): void
    {
        $ledger = $this->replay(
            ManualOperation::cashAdjustment($this->account, new \DateTimeImmutable('2025-01-01'), 'USD', '3620'),
            ManualOperation::blockCash($this->account, new \DateTimeImmutable('2025-01-02'), 'USD', '3620'),
            ManualOperation::blockCash($this->account, new \DateTimeImmutable('2025-03-01'), 'USD', '-620'),
        );

        self::assertSame(['USD' => '3620.0000'], $ledger->cash);
        self::assertSame(['USD' => '3000.0000'], $ledger->blockedCash);
    }

    public function testWarnsAboutClosingMoreThanIsOpen(): void
    {
        $sber = $this->share('SBER');

        $ledger = $this->replay(
            $this->buy($sber, 5, '250', '2025-06-10'),
            $this->close($sber, 8, '310', '2026-03-02'),
        );

        self::assertSame(['SBER: 3 of 8 securities were not open to be closed'], $ledger->warnings);
    }

    private function replay(ManualOperation ...$operations): ManualLedger
    {
        foreach ($operations as $operation) {
            $this->identify($operation, $this->nextId++);
        }
        usort($operations, static fn (ManualOperation $a, ManualOperation $b) => [$a->getExecutedAt(), $a->getId()] <=> [$b->getExecutedAt(), $b->getId()]);

        return (new ManualLedgerReplayer())->replay($operations);
    }

    /**
     * @param numeric-string $price
     * @param numeric-string|null $target
     * @param numeric-string|null $accrued
     */
    private function buy(Instrument $instrument, int $quantity, string $price, string $date, ?string $target = null, ?string $accrued = null, ManualOperationType $type = ManualOperationType::Buy): ManualOperation
    {
        return ManualOperation::open($this->account, $type, new \DateTimeImmutable($date), $instrument, $instrument->getTicker(), $instrument->getStockMarket(), $quantity, $price, $target, $accrued);
    }

    /**
     * @param numeric-string $price
     * @param numeric-string|null $accrued
     */
    private function close(Instrument $instrument, int $quantity, string $price, string $date, ?string $lot = null, ?string $accrued = null): ManualOperation
    {
        return ManualOperation::close($this->account, new \DateTimeImmutable($date), $instrument, $instrument->getTicker(), $instrument->getStockMarket(), $lot, $quantity, $price, $accrued);
    }

    private function share(string $ticker): Share
    {
        $share = new Share($ticker, $ticker, 'MOEX', 'RUB', '300', ShareTypeEnum::Stock->value);
        $this->identify($share);

        return $share;
    }

    private function identify(object $entity, ?int $id = null): void
    {
        static $instrumentIds = 100;
        $class = $entity instanceof Instrument ? Instrument::class : $entity::class;
        (new \ReflectionProperty($class, 'id'))->setValue($entity, $id ?? $instrumentIds++);
    }

    /**
     * @return list<array{string, int, string, ?string, ?string, ?true, ?string}>
     */
    private function lots(ManualLedger $ledger): array
    {
        $price = static fn (?string $price): ?string => $price !== null && str_contains($price, '.') ? rtrim(rtrim($price, '0'), '.') : $price;

        return array_map(static fn (Lot $lot) => [
            $lot->getKey(),
            $lot->getQuantity(),
            $price($lot->getOpenPrice()),
            $price($lot->getClosePrice()),
            $lot->getClosedAt()?->format('Y-m-d'),
            $lot->isBlocked() ?: null,
            $lot->getTarget(),
        ], $ledger->lots);
    }
}
