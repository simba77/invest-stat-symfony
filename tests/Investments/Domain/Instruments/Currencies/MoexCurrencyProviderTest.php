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
                ['RUB', 'USD', '83.48390', '2026-10-02'],
                ['RUB', 'CNY', '12.49630', '2026-10-02'],
            ],
            array_map(
                static fn (CurrencyRateInterface $rate) => [$rate->getBaseCurrency(), $rate->getTargetCurrency(), $rate->getRate(), $rate->getDate()->format('Y-m-d')],
                $rates,
            ),
        );
    }

    public function testHistoryKeepsTheLastRateOfEveryDayFromAllPages(): void
    {
        $requests = [];
        $pages = [
            ['2026-01-15', ['13:45:00' => '75.10', '18:30:00' => '75.30'], 0, 3],
            ['2026-01-16', ['18:30:00' => '78.00'], 2, 3],
        ];
        $responses = [];
        foreach (['USD/RUB', 'HKD/RUB', 'EUR/RUB', 'CNY/RUB'] as $pair) {
            foreach ($pages as [$day, $rates, $index, $total]) {
                $responses[] = static function (string $method, string $url) use (&$requests, $pair, $day, $rates, $index, $total): MockResponse {
                    $requests[] = $url;
                    $rows = '';
                    foreach ($rates as $time => $rate) {
                        $rows .= sprintf('<row tradedate="%s" tradetime="%s" secid="%s" rate="%s" clearing="mc" />', $day, $time, $pair, $rate);
                    }

                    return new MockResponse(sprintf(
                        '<?xml version="1.0" encoding="UTF-8"?><document><data id="securities"><rows>%s</rows></data>'
                        . '<data id="securities.cursor"><rows><row INDEX="%d" TOTAL="%d" PAGESIZE="2" /></rows></data></document>',
                        $rows,
                        $index,
                        $total,
                    ));
                };
            }
        }
        $provider = new MoexCurrencyProvider(new MoexHttpClient(new MockHttpClient($responses)));

        $rates = $provider->getRateHistory(new \DateTimeImmutable('2026-01-15'), new \DateTimeImmutable('2026-01-31'));

        self::assertSame(
            ['USD 2026-01-15 75.30', 'USD 2026-01-16 78.00'],
            array_slice(array_map(
                static fn (CurrencyRateInterface $rate) => sprintf('%s %s %s', $rate->getTargetCurrency(), $rate->getDate()->format('Y-m-d'), $rate->getRate()),
                $rates,
            ), 0, 2),
        );
        self::assertCount(8, $rates);
        self::assertStringContainsString('/securities/USD/RUB.xml?from=2026-01-15&till=2026-01-31&start=0', $requests[0]);
        self::assertStringContainsString('&start=2', $requests[1]);
    }

    private function providerAnswering(string $xml): MoexCurrencyProvider
    {
        return new MoexCurrencyProvider(new MoexHttpClient(new MockHttpClient(new MockResponse($xml))));
    }
}
