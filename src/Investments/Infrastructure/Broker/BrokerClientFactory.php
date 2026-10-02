<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Broker;

use App\Investments\Domain\BrokerSync\BrokerProvider;
use App\Investments\Domain\BrokerSync\Client\BrokerClientFactoryInterface;
use App\Investments\Domain\BrokerSync\Client\BrokerClientInterface;
use App\Investments\Infrastructure\Broker\TInvest\TInvestBrokerClient;

final readonly class BrokerClientFactory implements BrokerClientFactoryInterface
{
    public function __construct(
        private TInvestBrokerClient $tInvest,
    ) {
    }

    #[\Override]
    public function forProvider(BrokerProvider $provider): BrokerClientInterface
    {
        return match ($provider) {
            BrokerProvider::TInvest => $this->tInvest,
        };
    }
}
