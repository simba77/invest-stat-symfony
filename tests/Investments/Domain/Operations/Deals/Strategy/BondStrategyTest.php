<?php

declare(strict_types=1);

namespace App\Tests\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Investments\Domain\Operations\Deals\Strategy\BondStrategy;
use App\Shared\Domain\User;
use PHPUnit\Framework\TestCase;

class BondStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        if (! function_exists('bcdiv')) {
            self::markTestSkipped('The ext-bcmath extension is required for this test.');
        }
    }

    public function testGetCurrentAndPrevPriceIncludeAccumulatedCoupon(): void
    {
        $strategy = $this->createStrategy(
            new Bond(
                ticker: 'SU26238RMFS4',
                name: 'OFZ',
                stockMarket: 'MOEX',
                currency: 'RUB',
                price: '101.5',
                prevPrice: '100.5',
                lotSize: '1000',
                couponAccumulated: '1.2345',
            )
        );

        self::assertSame('1016.2345', $strategy->getCurrentPrice());
        self::assertSame('1006.2345', $strategy->getPrevPrice());
    }

    public function testGetCurrentAndPrevPriceFallbackForNullValues(): void
    {
        $strategy = $this->createStrategy(
            new Bond(
                ticker: 'SU26238RMFS4',
                name: 'OFZ',
                stockMarket: 'MOEX',
                currency: 'RUB',
                price: '123',
                prevPrice: null,
                lotSize: '10',
                couponAccumulated: null,
            )
        );

        self::assertSame('12.3000', $strategy->getCurrentPrice());
        self::assertSame('0.0000', $strategy->getPrevPrice());
    }

    private function createStrategy(Bond $bond): BondStrategy
    {
        $user = new User();
        $account = new Account(userId: 1, name: 'Test account');
        $deal = new Deal(
            user: $user,
            account: $account,
            ticker: $bond->getTicker(),
            stockMarket: $bond->getStockMarket(),
            status: DealStatus::Active,
            type: DealType::Long,
            quantity: 1,
            buyPrice: '100',
        );
        $deal->setBond($bond);

        return new BondStrategy($deal);
    }
}
