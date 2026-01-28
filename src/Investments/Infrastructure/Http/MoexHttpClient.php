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
     * @return list<mixed>
     */
    public function getCurrencyRates(): array
    {
        $data = $this->getData('/iss/statistics/engines/futures/markets/indicativerates/securities.xml');
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $currencies = $propertyAccessor->getValue($data, '[data][0][rows][row]') ?? [];
        return array_column($currencies, '@attributes');
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
