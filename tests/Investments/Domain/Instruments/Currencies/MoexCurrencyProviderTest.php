<?php

declare(strict_types=1);

namespace App\Tests\Investments\Domain\Instruments\Currencies;

use App\Investments\Domain\Instruments\Currencies\CurrencyRateInterface;
use App\Investments\Domain\Instruments\Currencies\MoexCurrencyProvider;
use App\Investments\Infrastructure\Http\MoexHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class MoexCurrencyProviderTest extends TestCase
{
    public function testTakesLatestRateOfEveryPair(): void
    {
        $provider = $this->providerAnswering(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <document>
            <data id="securities">
            <rows>
            <row tradedate="2026-10-01" tradetime="19:00:00" secid="USD/RUB" rate="82.90000" clearing="mc" />
            <row tradedate="2026-10-02" tradetime="14:00:00" secid="USD/RUB" rate="83.24540" clearing="tc" />
            <row tradedate="2026-10-02" tradetime="19:00:00" secid="USD/RUB" rate="83.48390" clearing="mc" />
            <row tradedate="2026-10-02" tradetime="19:00:00" secid="CNY/RUB" rate="12.49630" clearing="mc" />
            <row tradedate="2026-10-02" tradetime="14:00:00" secid="CNY/RUB" rate="12.45960" clearing="tc" />
            <row tradedate="2026-10-02" tradetime="19:00:00" secid="GBP/RUB" rate="110.29890" clearing="mc" />
            </rows>
            </data>
            <data id="securities.list">
            <rows>
            <row secid="USD/RUB" title="USD/RUB" />
            </rows>
            </data>
            </document>
            XML);

        $rates = $provider->getCurrencyRates();

        self::assertSame(
            [
                ['RUB', 'USD', '83.48390'],
                ['RUB', 'CNY', '12.49630'],
            ],
            array_map(
                static fn (CurrencyRateInterface $rate) => [$rate->getBaseCurrency(), $rate->getTargetCurrency(), $rate->getRate()],
                $rates,
            ),
        );
    }

    private function providerAnswering(string $xml): MoexCurrencyProvider
    {
        return new MoexCurrencyProvider(new MoexHttpClient(new MockHttpClient(new MockResponse($xml))));
    }
}
