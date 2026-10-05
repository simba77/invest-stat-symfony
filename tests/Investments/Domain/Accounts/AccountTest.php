<?php

declare(strict_types=1);

namespace App\Tests\Investments\Domain\Accounts;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Securities\ShareTypeEnum;
use App\Investments\Domain\Instruments\Share;
use App\Shared\Domain\User;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    public function testChargesTradesByTariff(): void
    {
        $account = new Account(new User(), 'Broker', commission: '0.3', futuresCommission: '2.5');

        self::assertSame('7.50', $account->tradeCommission(new Share('SBER', 'Сбер', 'MOEX', 'RUB', '300', ShareTypeEnum::Stock->value), '250', 10));
        // 0.3% of 2 × 1000 × 98.5%
        self::assertSame('5.91', $account->tradeCommission(new Bond('SU26238RMFS4', 'ОФЗ', 'MOEX', 'RUB', '95', lotSize: '1000'), '98.5', 2));
        self::assertSame('7.50', $account->tradeCommission(new Future('SiH6', 'Si', 'MOEX', 'RUB', '90000'), '85000', 3));
        // A security the catalogue does not know is charged by its price
        self::assertSame('0.60', $account->tradeCommission(null, '50', 4));
        self::assertSame('0.01', $account->tradeCommission(null, '1.7', 1));
        self::assertSame('0.00', $account->tradeCommission(null, '1.55', 1));
    }
}
