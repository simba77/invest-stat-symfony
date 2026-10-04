<?php

declare(strict_types=1);

namespace App\Tests\Investments\Domain\Instruments\Currencies;

use App\Investments\Domain\Instruments\Currencies\Currency;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    /**
     * @dataProvider currencies
     */
    public function testNamesCurrencyByCode(string $code, string $symbol, string $title): void
    {
        self::assertSame($symbol, Currency::symbolOf($code));
        self::assertSame($title, Currency::titleOf($code));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function currencies(): iterable
    {
        yield 'rouble' => ['RUB', '₽', 'Russian Rouble'];
        yield 'dollar' => ['USD', '$', 'US Dollar'];
        yield 'euro' => ['EUR', '€', 'Euro'];
        yield 'yuan' => ['CNY', '¥', 'Chinese Yuan'];
        yield 'currency without a symbol here' => ['CHF', 'CHF', 'CHF'];
    }
}
