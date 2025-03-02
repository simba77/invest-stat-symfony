<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Http;

use Metaseller\TinkoffInvestApi2\TinkoffClientsFactory;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tinkoff\Invest\V1\Bond;
use Tinkoff\Invest\V1\Future;
use Tinkoff\Invest\V1\GetLastPricesRequest;
use Tinkoff\Invest\V1\GetLastPricesResponse;
use Tinkoff\Invest\V1\InstrumentsRequest;
use Tinkoff\Invest\V1\LastPrice;
use Tinkoff\Invest\V1\Share;
use Tinkoff\Invest\V1\SharesResponse;

class TInvestHttpClient
{
    private TinkoffClientsFactory $client;

    public function __construct(ParameterBagInterface $parameters)
    {
        $token = $parameters->get('app.tinkoff.apiKey');
        if (! is_string($token) || empty($token)) {
            throw new RuntimeException('Token is not set');
        }

        $this->client = TinkoffClientsFactory::create($token);
    }

    /**
     * @param array<int, string> $uids
     * @return iterable<LastPrice>
     */
    public function getLastPricesByUids(array $uids): iterable
    {
        $instrumentsRequest = new GetLastPricesRequest();
        $instrumentsRequest->setInstrumentId($uids);

        /** @var GetLastPricesResponse $response */
        [$response] = $this->client->marketDataServiceClient->GetLastPrices($instrumentsRequest)->wait();
        return $response->getLastPrices();
    }

    /**
     * @return iterable<Share>
     */
    public function getAllShares(): iterable
    {
        $instrumentsRequest = new InstrumentsRequest();
        /** @var SharesResponse $response */
        [$response] = $this->client->instrumentsServiceClient->Shares($instrumentsRequest)->wait();
        return $response->getInstruments();
    }

    /**
     * @return iterable<Bond>
     */
    public function getAllBonds(): iterable
    {
        $instrumentsRequest = new InstrumentsRequest();
        /** @var SharesResponse $response */
        [$response] = $this->client->instrumentsServiceClient->Bonds($instrumentsRequest)->wait();
        return $response->getInstruments();
    }

    /**
     * @return iterable<Future>
     */
    public function getAllFutures(): iterable
    {
        $instrumentsRequest = new InstrumentsRequest();
        /** @var SharesResponse $response */
        [$response] = $this->client->instrumentsServiceClient->Futures($instrumentsRequest)->wait();
        return $response->getInstruments();
    }
}
