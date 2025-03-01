<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Http;

use Metaseller\TinkoffInvestApi2\TinkoffClientsFactory;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tinkoff\Invest\V1\GetLastPricesRequest;
use Tinkoff\Invest\V1\LastPrice;

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

        /**
         * @var \Tinkoff\Invest\V1\GetLastPricesResponse $response
         */
        [$response] = $this->client->marketDataServiceClient->GetLastPrices($instrumentsRequest)->wait();
        return $response->getLastPrices();
    }
}
