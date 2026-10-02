<?php

declare(strict_types=1);

namespace App\Tests\Investments\Domain\BrokerSync\Ledger;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerOperation;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\BrokerProvider;
use App\Investments\Domain\BrokerSync\Client\ExternalOperation;
use App\Investments\Domain\BrokerSync\FeeAllocation;
use App\Investments\Domain\BrokerSync\InstrumentKind;
use App\Investments\Domain\BrokerSync\Ledger\Ledger;
use App\Investments\Domain\BrokerSync\Ledger\LedgerReplayer;
use App\Investments\Domain\BrokerSync\Ledger\Lot;
use App\Investments\Domain\Instruments\ShareSplit;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Tests\Investments\BrokerSync\Operations;
use PHPUnit\Framework\TestCase;

final class LedgerReplayerTest extends TestCase
{
    public function testMatchesSalesWithPurchasesFirstInFirstOut(): void
    {
        $ledger = $this->replay([
            Operations::buy('1', '2026-01-10 10:00:00', 'SBER', 10, '100', '1'),
            Operations::buy('2', '2026-01-11 10:00:00', 'SBER', 5, '110', '0.5'),
            Operations::sell('3', '2026-01-12 10:00:00', 'SBER', 12, '120', '1.2'),
        ]);

        self::assertSame([
            ['1', 'long', 10, '100.0000', '1.0000', '120.0000', '1.0000'],
            ['2', 'long', 2, '110.0000', '0.2000', '120.0000', '0.2000'],
            ['2#1', 'long', 3, '110.0000', '0.3000', null, '0.0000'],
        ], $this->lots($ledger));
        self::assertSame('2026-01-12 10:00:00', $ledger->lots[0]->getClosedAt()?->format('Y-m-d H:i:s'));
        self::assertSame('2026-01-11 10:00:00', $ledger->lots[2]->getOpenedAt()->format('Y-m-d H:i:s'));
        self::assertSame([Operations::uid('SBER') => 3], $ledger->positions);
    }

    public function testSaleBeyondPositionOpensShortThatPurchaseCloses(): void
    {
        $ledger = $this->replay([
            Operations::buy('1', '2026-01-10 10:00:00', 'GAZP', 2, '150'),
            Operations::sell('2', '2026-01-11 10:00:00', 'GAZP', 5, '160', '5'),
            Operations::buy('3', '2026-01-12 10:00:00', 'GAZP', 4, '140', '4'),
        ]);

        self::assertSame([
            ['1', 'long', 2, '150.0000', '0.0000', '160.0000', '2.0000'],
            ['2', 'short', 3, '160.0000', '3.0000', '140.0000', '3.0000'],
            ['3', 'long', 1, '140.0000', '1.0000', null, '0.0000'],
        ], $this->lots($ledger));
        self::assertSame([Operations::uid('GAZP') => 1], $ledger->positions);
    }

    public function testCountsLinkedFeeInsteadOfCommissionReportedInTrade(): void
    {
        $ledger = $this->replay([
            Operations::buy('1', '2024-03-13 15:04:13', 'SBER', 30, '299.85', '26.99'),
            Operations::fee('2', '2024-03-13 15:04:14', '-26.99', parentId: '1'),
        ]);

        self::assertSame('26.990000000', $ledger->lots[0]->getOpenCommission());
    }

    public function testSpreadsDailyFeeOverTradesOfTheDayByTurnover(): void
    {
        $ledger = $this->replay([
            Operations::buy('1', '2026-02-02 07:00:00', 'SBER', 10, '100'),
            Operations::buy('2', '2026-02-02 12:00:00', 'GAZP', 20, '150'),
            Operations::fee('3', '2026-02-02 20:00:00', '-4'),
        ], FeeAllocation::DailyProRata);

        self::assertSame(['1.000000000', '3.000000000'], array_map(static fn (Lot $lot) => $lot->getOpenCommission(), $ledger->lots));
    }

    public function testPoolsTradeCommissionsOfTheDayWhenFeesAreDaily(): void
    {
        $ledger = $this->replay([
            Operations::buy('1', '2026-02-02 07:00:00', 'SBER', 10, '100', '9'),
            Operations::buy('2', '2026-02-02 12:00:00', 'GAZP', 30, '100', '1'),
        ], FeeAllocation::DailyProRata);

        self::assertSame(['2.500000000', '7.500000000'], array_map(static fn (Lot $lot) => $lot->getOpenCommission(), $ledger->lots));
    }

    public function testChargesFeeAfterQuietDayToLastTradingDay(): void
    {
        $ledger = $this->replay([
            Operations::buy('1', '2026-01-30 10:00:00', 'SBER', 10, '100'),
            // Saturday morning in Moscow
            Operations::fee('2', '2026-01-31 05:00:00', '-3'),
        ]);

        self::assertSame('3.000000000', $ledger->lots[0]->getOpenCommission());
    }

    public function testKeepsFeeWithoutTradesNearbyOutOfLots(): void
    {
        $ledger = $this->replay([
            Operations::buy('1', '2026-01-01 10:00:00', 'SBER', 10, '100'),
            Operations::fee('2', '2026-02-01 10:00:00', '-3'),
            Operations::fee('3', '2026-02-02 10:00:00', '-3.96', BrokerOperationType::ManagementFee),
        ]);

        self::assertSame(0, bccomp('0', $ledger->lots[0]->getOpenCommission(), 9));
        self::assertSame(['RUB' => '-1006.960000000'], $ledger->cash);
    }

    public function testAppliesSplitToLotsOpenOnItsDate(): void
    {
        $ledger = $this->replay(
            [
                Operations::buy('1', '2026-03-12 08:56:22', 'T', 3, '3376.4'),
                Operations::buy('2', '2026-03-13 08:00:00', 'T', 1, '3400'),
                Operations::buy('3', '2026-04-17 07:01:08', 'T', 5, '326.46'),
                Operations::sell('4', '2026-07-27 08:32:31', 'T', 45, '263.6'),
            ],
            splits: [new ShareSplit('T', 'MOEX', new \DateTimeImmutable('2026-04-17'), 1, 10)],
        );

        self::assertSame([
            ['1', 'long', 30, '337.6400', '0.0000', '263.6000', '0.0000'],
            ['2', 'long', 10, '340.0000', '0.0000', '263.6000', '0.0000'],
            ['3', 'long', 5, '326.4600', '0.0000', '263.6000', '0.0000'],
        ], $this->lots($ledger));
        self::assertSame([], $ledger->positions);
    }

    public function testAppliesSplitAfterLastOperationByDateOfReplay(): void
    {
        $operations = [Operations::buy('1', '2026-03-12 08:56:22', 'T', 4, '3376')];
        $splits = [new ShareSplit('T', 'MOEX', new \DateTimeImmutable('2026-04-17'), 1, 10)];

        $before = $this->replay($operations, splits: $splits, asOf: '2026-04-16 12:00:00');
        $after = $this->replay($operations, splits: $splits, asOf: '2026-04-17 12:00:00');

        self::assertSame([Operations::uid('T') => 4], $before->positions);
        self::assertSame([Operations::uid('T') => 40], $after->positions);
    }

    public function testIgnoresSplitsOfOtherExchange(): void
    {
        $ledger = $this->replay(
            [Operations::buy('1', '2026-03-12 08:56:22', 'T', 4, '3376', classCode: 'SPBXM')],
            splits: [new ShareSplit('T', 'MOEX', new \DateTimeImmutable('2026-04-17'), 1, 10)],
        );

        self::assertSame([Operations::uid('T') => 4], $ledger->positions);
    }

    public function testWithholdsTaxFromNearestPayoutOfTheInstrument(): void
    {
        $ledger = $this->replay([
            Operations::payout('1', BrokerOperationType::Dividend, '2026-05-28 06:58:48', 'T', '207'),
            Operations::payout('2', BrokerOperationType::Dividend, '2026-08-04 14:52:08', 'SBER', '188.2'),
            Operations::payout('3', BrokerOperationType::DividendTax, '2026-08-04 14:52:08', 'SBER', '-24'),
            Operations::payout('4', BrokerOperationType::DividendTax, '2026-05-28 06:58:48', 'T', '-27'),
            Operations::payout('5', BrokerOperationType::Coupon, '2026-06-01 10:00:00', 'SU26238RMFS4', '35.4', InstrumentKind::Bond, 'TQOB'),
        ]);

        self::assertSame(
            [['1', 'T', '207', '27.000000000', '180.000000000'], ['2', 'SBER', '188.2', '24.000000000', '164.200000000']],
            array_map(static fn ($payout) => [$payout->getExternalId(), $payout->getInstrument()->ticker, $payout->getGross(), $payout->getTax(), $payout->getNet()], $ledger->dividends),
        );
        self::assertCount(1, $ledger->coupons);
        self::assertSame('0', $ledger->coupons[0]->getTax());
        self::assertSame([], $ledger->warnings);
    }

    public function testCollectsDepositsAndWithdrawalsAndCash(): void
    {
        $ledger = $this->replay([
            Operations::deposit('1', '2024-04-04 21:37:12', '40000'),
            Operations::buy('2', '2024-04-05 07:15:09', 'UGLD', 24000, '0.9325'),
            Operations::withdrawal('3', '2024-04-15 11:32:39', '-2723.23'),
        ]);

        self::assertSame(
            [['1', '40000'], ['3', '-2723.23']],
            array_map(static fn ($flow) => [$flow->externalId, $flow->amount], $ledger->cashFlows),
        );
        self::assertSame(['RUB' => '14896.770000000'], $ledger->cash);
    }

    public function testCountsCurrencyAndFuturesTradesOnlyInCash(): void
    {
        $ledger = $this->replay([
            Operations::buy('1', '2026-01-10 10:00:00', 'USD000UTSTOM', 100, '90', kind: InstrumentKind::Currency, classCode: 'CETS'),
            Operations::buy('2', '2026-01-10 11:00:00', 'SiH6', 1, '1000', kind: InstrumentKind::Future, classCode: 'SPBFUT'),
        ]);

        self::assertSame([], $ledger->lots);
        self::assertSame(['SiH6: future trades are not turned into deals yet, they are counted only in cash'], $ledger->warnings);
        self::assertSame(['RUB' => '-10000.000000000'], $ledger->cash);
    }

    public function testClosesBondAtMaturityAndLowersPriceOnRepayment(): void
    {
        $ledger = $this->replay([
            Operations::buy('1', '2026-01-10 10:00:00', 'RU000A1', 10, '1000', kind: InstrumentKind::Bond, classCode: 'TQCB'),
            Operations::payout('2', BrokerOperationType::BondRepayment, '2026-03-10 10:00:00', 'RU000A1', '2500', InstrumentKind::Bond, 'TQCB'),
            Operations::payout('3', BrokerOperationType::BondMaturity, '2026-06-10 10:00:00', 'RU000A1', '7500', InstrumentKind::Bond, 'TQCB'),
        ]);

        self::assertSame([['1', 'long', 10, '750.0000', '0.0000', '750.0000', '0.0000']], $this->lots($ledger));
        self::assertSame([], $ledger->positions);
    }

    public function testWithdrawsSecuritiesWithoutResult(): void
    {
        $out = new ExternalOperation(
            '2', null, BrokerOperationType::SecuritiesOut, 'OPERATION_TYPE_OUT_MULTI', Operations::buy('x', '2026-01-01', 'SBER', 1, '1')->state,
            new \DateTimeImmutable('2026-02-01 10:00:00'), Operations::uid('SBER'), InstrumentKind::Share, 'SBER', 'TQBR', null,
            4, null, '0', 'RUB', null, null, null, [],
        );

        $ledger = $this->replay([Operations::buy('1', '2026-01-10 10:00:00', 'SBER', 10, '100'), $out]);

        self::assertSame([
            ['1', 'long', 4, '100.0000', '0.0000', '100.0000', '0.0000'],
            ['1#1', 'long', 6, '100.0000', '0.0000', null, '0.0000'],
        ], $this->lots($ledger));
    }

    /**
     * @param list<ExternalOperation> $operations
     * @param list<ShareSplit> $splits
     */
    private function replay(
        array $operations,
        FeeAllocation $allocation = FeeAllocation::PerOperation,
        array $splits = [],
        string $asOf = '2026-10-02 12:00:00',
    ): Ledger {
        $link = new BrokerAccountLink(new Account(1, 'Broker'), BrokerProvider::TInvest, 'token', '2000', 'Broker', null, $allocation, true);
        $journal = array_map(static fn (ExternalOperation $operation) => new BrokerOperation($link, $operation), $operations);

        return (new LedgerReplayer())->replay($journal, $splits, $allocation, new \DateTimeImmutable($asOf));
    }

    /**
     * @return list<array{string, string, int, string, string, string|null, string}>
     */
    private function lots(Ledger $ledger): array
    {
        return array_map(static fn (Lot $lot) => [
            $lot->getKey(),
            $lot->getDirection() === DealType::Long ? 'long' : 'short',
            $lot->getQuantity(),
            self::number($lot->getOpenPrice()),
            self::number($lot->getOpenCommission()),
            $lot->getClosePrice() !== null ? self::number($lot->getClosePrice()) : null,
            self::number($lot->getCloseCommission()),
        ], $ledger->lots);
    }

    /**
     * @param numeric-string $value
     */
    private static function number(string $value): string
    {
        return bcadd($value, '0', 4);
    }
}
