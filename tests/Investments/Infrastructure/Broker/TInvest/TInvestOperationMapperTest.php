<?php

declare(strict_types=1);

namespace App\Tests\Investments\Infrastructure\Broker\TInvest;

use App\Investments\Domain\BrokerSync\BrokerOperationState;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\InstrumentKind;
use App\Investments\Infrastructure\Broker\TInvest\TInvestOperationMapper;
use PHPUnit\Framework\TestCase;
use Tinkoff\Invest\V1\OperationItem;

final class TInvestOperationMapperTest extends TestCase
{
    public function testMapsTradeFilledInSeveralParts(): void
    {
        $operation = (new TInvestOperationMapper())->map($this->item([
            'id'             => '100',
            'date'           => '2026-09-16T10:07:40Z',
            'type'           => 'OPERATION_TYPE_SELL',
            'state'          => 'OPERATION_STATE_EXECUTED',
            'instrumentUid'  => 'uid-lkoh',
            'instrumentKind' => 'INSTRUMENT_TYPE_SHARE',
            'ticker'         => 'LKOH',
            'classCode'      => 'TQBR',
            'name'           => 'Lukoil',
            'payment'        => ['currency' => 'rub', 'units' => '21629'],
            'price'          => ['currency' => 'rub', 'units' => '5407', 'nano' => 250000000],
            'commission'     => ['currency' => 'rub', 'units' => '-6', 'nano' => -490000000],
            'quantity'       => '4',
            'quantityDone'   => '4',
            'tradesInfo'     => ['trades' => [
                ['num' => '1', 'date' => '2026-09-16T10:07:40.171Z', 'quantity' => '3', 'price' => ['currency' => 'rub', 'units' => '5407']],
                ['num' => '2', 'date' => '2026-09-16T10:07:40.171Z', 'quantity' => '1', 'price' => ['currency' => 'rub', 'units' => '5407', 'nano' => 500000000]],
            ]],
        ]));

        self::assertSame('100', $operation->id);
        self::assertSame(BrokerOperationType::Sell, $operation->type);
        self::assertSame('OPERATION_TYPE_SELL', $operation->rawType);
        self::assertSame(BrokerOperationState::Executed, $operation->state);
        self::assertSame('2026-09-16 10:07:40', $operation->executedAt->format('Y-m-d H:i:s'));
        self::assertSame('uid-lkoh', $operation->instrumentUid);
        self::assertSame(InstrumentKind::Share, $operation->instrumentKind);
        self::assertSame('LKOH', $operation->ticker);
        self::assertSame('TQBR', $operation->classCode);
        self::assertSame(4, $operation->quantity);
        self::assertSame('5407.125000000', $operation->price);
        self::assertSame('21629.000000000', $operation->payment);
        self::assertSame('RUB', $operation->currency);
        self::assertSame('6.490000000', $operation->commission);
        self::assertNull($operation->accruedInterest);
        self::assertSame('LKOH', $operation->payload['ticker']);
    }

    public function testCountsOnlyExecutedPartOfOrder(): void
    {
        $operation = (new TInvestOperationMapper())->map($this->item([
            'id'           => '101',
            'date'         => '2026-09-16T10:00:00Z',
            'type'         => 'OPERATION_TYPE_BUY',
            'state'        => 'OPERATION_STATE_PROGRESS',
            'payment'      => ['currency' => 'rub', 'units' => '-100'],
            'price'        => ['currency' => 'rub', 'units' => '10'],
            'quantity'     => '20',
            'quantityRest' => '10',
            'quantityDone' => '10',
        ]));

        self::assertSame(BrokerOperationType::Buy, $operation->type);
        self::assertSame(BrokerOperationState::InProgress, $operation->state);
        self::assertSame(10, $operation->quantity);
        self::assertSame('10.000000000', $operation->price);
    }

    public function testMapsBondTradeWithAccruedInterest(): void
    {
        $operation = (new TInvestOperationMapper())->map($this->item([
            'id'             => '102',
            'date'           => '2024-09-13T13:07:14Z',
            'type'           => 'OPERATION_TYPE_BUY',
            'state'          => 'OPERATION_STATE_EXECUTED',
            'instrumentKind' => 'INSTRUMENT_TYPE_BOND',
            'payment'        => ['currency' => 'rub', 'units' => '-18497', 'nano' => -360000000],
            'price'          => ['currency' => 'rub', 'units' => '524', 'nano' => 10000000],
            'accruedInt'     => ['currency' => 'rub', 'units' => '681', 'nano' => 20000000],
            'quantity'       => '34',
        ]));

        self::assertSame(InstrumentKind::Bond, $operation->instrumentKind);
        self::assertSame(34, $operation->quantity);
        self::assertSame('524.010000000', $operation->price);
        self::assertSame('681.020000000', $operation->accruedInterest);
    }

    public function testLinksFeeToItsTrade(): void
    {
        $operation = (new TInvestOperationMapper())->map($this->item([
            'id'                => '103',
            'parentOperationId' => '100',
            'date'              => '2024-04-05T07:39:55Z',
            'type'              => 'OPERATION_TYPE_BROKER_FEE',
            'state'             => 'OPERATION_STATE_EXECUTED',
            'payment'           => ['currency' => 'rub', 'units' => '-1', 'nano' => -710000000],
        ]));

        self::assertSame(BrokerOperationType::TradeFee, $operation->type);
        self::assertSame('100', $operation->parentId);
        self::assertSame('-1.710000000', $operation->payment);
        self::assertNull($operation->price);
        self::assertNull($operation->commission);
        self::assertNull($operation->instrumentKind);
    }

    /**
     * @dataProvider operationTypes
     */
    public function testMapsOperationTypes(string $rawType, int $units, BrokerOperationType $expected): void
    {
        $operation = (new TInvestOperationMapper())->map($this->item([
            'id'      => '104',
            'date'    => '2026-10-01T22:00:00Z',
            'type'    => $rawType,
            'state'   => 'OPERATION_STATE_EXECUTED',
            'payment' => ['currency' => 'rub', 'units' => (string) $units],
        ]));

        self::assertSame($expected, $operation->type);
    }

    /**
     * @return iterable<string, array{string, int, BrokerOperationType}>
     */
    public static function operationTypes(): iterable
    {
        yield 'deposit' => ['OPERATION_TYPE_INPUT', 1000, BrokerOperationType::Deposit];
        yield 'withdrawal' => ['OPERATION_TYPE_OUTPUT', -1000, BrokerOperationType::Withdrawal];
        yield 'transfer in' => ['OPERATION_TYPE_TRANS_BS_BS', 500, BrokerOperationType::Deposit];
        yield 'transfer out' => ['OPERATION_TYPE_TRANS_BS_BS', -500, BrokerOperationType::Withdrawal];
        yield 'autofollow fee' => ['OPERATION_TYPE_TRACK_MFEE', -3, BrokerOperationType::ManagementFee];
        yield 'autofollow result fee' => ['OPERATION_TYPE_TRACK_PFEE', -161, BrokerOperationType::PerformanceFee];
        yield 'service fee' => ['OPERATION_TYPE_SERVICE_FEE', -290, BrokerOperationType::ServiceFee];
        yield 'dividend' => ['OPERATION_TYPE_DIVIDEND', 188, BrokerOperationType::Dividend];
        yield 'dividend tax' => ['OPERATION_TYPE_DIVIDEND_TAX', -24, BrokerOperationType::DividendTax];
        yield 'coupon' => ['OPERATION_TYPE_COUPON', 35, BrokerOperationType::Coupon];
        yield 'coupon tax' => ['OPERATION_TYPE_BOND_TAX', -5, BrokerOperationType::CouponTax];
        yield 'amortization' => ['OPERATION_TYPE_BOND_REPAYMENT', 250, BrokerOperationType::BondRepayment];
        yield 'maturity' => ['OPERATION_TYPE_BOND_REPAYMENT_FULL', 1000, BrokerOperationType::BondMaturity];
        yield 'tax' => ['OPERATION_TYPE_TAX', -22, BrokerOperationType::Tax];
        yield 'tax refund' => ['OPERATION_TYPE_TAX_CORRECTION', 17, BrokerOperationType::TaxCorrection];
        yield 'securities in' => ['OPERATION_TYPE_INP_MULTI', 0, BrokerOperationType::SecuritiesIn];
        yield 'variation margin' => ['OPERATION_TYPE_ACCRUING_VARMARGIN', 120, BrokerOperationType::VariationMargin];
        yield 'unknown' => ['OPERATION_TYPE_FUNDING', 0, BrokerOperationType::Other];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function item(array $data): OperationItem
    {
        $item = new OperationItem();
        $item->mergeFromJsonString(json_encode($data, JSON_THROW_ON_ERROR));

        return $item;
    }
}
