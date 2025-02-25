<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InvestCabHttpClient
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->client = $httpClient->withOptions(
            [
                'base_uri' => 'https://investcab.ru/api/',
            ]
        );
    }

    private function parseContent(string $content): mixed
    {
        return json_decode(json_decode($content, true, 512, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getDataAboutTicker(string $ticker): mixed
    {
        $data = $this->client->request('GET', 'symbol?symbol=' . $ticker)->getContent();
        return $this->parseContent($data);
    }

    public function getPriceByTicker(string $ticker): string
    {
        $url = 'chistory?symbol=' . $ticker . '&resolution=30&from=' . (time() - 86400 * 3) . '&to=' . time();
        $data = $this->client->request('GET', $url)->getContent();
        $price = $this->parseContent($data)['c'];
        $price = end($price);
        if (empty($price)) {
            throw new RuntimeException('Unable to get price for ticker: ' . $ticker);
        }
        return (string) $price;
    }
}
