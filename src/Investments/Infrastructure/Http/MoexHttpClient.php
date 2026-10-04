<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Http;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MoexHttpClient
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->client = $httpClient->withOptions(
            [
                'base_uri' => 'https://iss.moex.com/',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     */
    public function getData(string $url): array
    {
        $xmlDataString = $this->client->request('GET', $url)->getContent();
        $xmlObject = simplexml_load_string($xmlDataString);
        return json_decode(json_encode($xmlObject, JSON_THROW_ON_ERROR), true) ?? [];
    }

    /**
     * Indicative rates of the recent clearing sessions, several rows per currency pair.
     *
     * @return list<array{tradedate: string, tradetime: string, secid: string, rate: string, clearing: string}>
     */
    public function getCurrencyRates(): array
    {
        $data = $this->getData('/iss/statistics/engines/futures/markets/indicativerates/securities.xml');
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $currencies = $propertyAccessor->getValue($data, '[data][0][rows][row]') ?? [];

        /** @var list<array{tradedate: string, tradetime: string, secid: string, rate: string, clearing: string}> */
        return array_column($currencies, '@attributes');
    }

    /**
     * Indicative rates of a currency pair, such as "USD/RUB", for every clearing session of the period.
     *
     * @return list<array{tradedate: string, tradetime: string, secid: string, rate: string, clearing: string}>
     */
    public function getCurrencyRateHistory(string $pair, \DateTimeImmutable $from, \DateTimeImmutable $till): array
    {
        $rows = [];
        $start = 0;
        do {
            $data = $this->getData(sprintf(
                '/iss/statistics/engines/futures/markets/indicativerates/securities/%s.xml?from=%s&till=%s&start=%d',
                $pair,
                $from->format('Y-m-d'),
                $till->format('Y-m-d'),
                $start,
            ));
            $page = $this->rowsOf($data, 'securities');
            array_push($rows, ...$page);
            $cursor = $this->rowsOf($data, 'securities.cursor')[0] ?? null;
            $start += (int) ($cursor['PAGESIZE'] ?? 0);
        } while ($page !== [] && $cursor !== null && $start < (int) $cursor['TOTAL']);

        /** @var list<array{tradedate: string, tradetime: string, secid: string, rate: string, clearing: string}> */
        return $rows;
    }

    /**
     * Splits and consolidations of shares traded on MOEX.
     *
     * @return list<array{tradedate: string, secid: string, before: string, after: string}>
     */
    public function getSplits(): array
    {
        $data = $this->getData('/iss/statistics/engines/stock/splits.xml');
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $rows = $propertyAccessor->getValue($data, '[data][rows][row]') ?? [];
        // A single row is not wrapped into a list by the XML to array conversion
        if (isset($rows['@attributes'])) {
            $rows = [$rows];
        }

        /** @var list<array{tradedate: string, secid: string, before: string, after: string}> */
        return array_column($rows, '@attributes');
    }

    /**
     * The rows of a data block of an ISS response, found by its id.
     *
     * @param array<string, mixed> $data
     * @return list<array<string, string>>
     */
    private function rowsOf(array $data, string $id): array
    {
        $blocks = $data['data'] ?? [];
        // A single block is not wrapped into a list by the XML to array conversion
        if (is_array($blocks) && isset($blocks['@attributes'])) {
            $blocks = [$blocks];
        }

        foreach (is_array($blocks) ? $blocks : [] as $block) {
            if (! is_array($block) || ($block['@attributes']['id'] ?? null) !== $id) {
                continue;
            }
            $rows = $block['rows']['row'] ?? [];
            if (! is_array($rows)) {
                return [];
            }
            if (isset($rows['@attributes'])) {
                $rows = [$rows];
            }

            /** @var list<array<string, string>> */
            return array_column($rows, '@attributes');
        }

        return [];
    }

    /**
     * @return array{shares: list<mixed>, marketData: list<mixed>}
     */
    public function getSharesByBoard(string $board): array
    {
        $data = $this->getData('/iss/engines/stock/markets/shares/boards/' . $board . '/securities.xml');
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $shares = $propertyAccessor->getValue($data, '[data][0][rows][row]') ?? [];
        $marketData = $propertyAccessor->getValue($data, '[data][1][rows][row]') ?? [];

        return [
            'shares'     => array_column($shares, '@attributes'),
            'marketData' => array_column($marketData, '@attributes'),
        ];
    }

    /**
     * @return array{bonds: list<mixed>, marketData: list<mixed>}
     */
    public function getBonds(): array
    {
        $data = $this->getData('/iss/engines/stock/markets/bonds/boards/TQCB/securities.xml');
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $bonds = $propertyAccessor->getValue($data, '[data][0][rows][row]') ?? [];
        $marketData = $propertyAccessor->getValue($data, '[data][1][rows][row]') ?? [];

        return [
            'bonds'      => array_column($bonds, '@attributes'),
            'marketData' => array_column($marketData, '@attributes'),
        ];
    }

    /**
     * @return array{bonds: list<mixed>, marketData: list<mixed>}
     */
    public function getBondsByBoard(string $board): array
    {
        $data = $this->getData('/iss/engines/stock/markets/bonds/boards/' . $board . '/securities.xml');
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $bonds = $propertyAccessor->getValue($data, '[data][0][rows][row]') ?? [];
        $marketData = $propertyAccessor->getValue($data, '[data][1][rows][row]') ?? [];

        return [
            'bonds'      => array_column($bonds, '@attributes'),
            'marketData' => array_column($marketData, '@attributes'),
        ];
    }

    /**
     * @return array{futures: list<mixed>, marketData: list<mixed>}
     */
    public function getFutures(): array
    {
        $data = $this->getData('/iss/engines/futures/markets/forts/securities.xml');
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $bonds = $propertyAccessor->getValue($data, '[data][0][rows][row]') ?? [];
        $marketData = $propertyAccessor->getValue($data, '[data][1][rows][row]') ?? [];

        return [
            'futures'    => array_column($bonds, '@attributes'),
            'marketData' => array_column($marketData, '@attributes'),
        ];
    }
}
