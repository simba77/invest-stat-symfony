<?php

declare(strict_types=1);

namespace App\Tests\Investments\BrokerSync;

use App\Investments\Domain\BrokerSync\BrokerProvider;
use App\Investments\Domain\BrokerSync\Client\BrokerClientFactoryInterface;
use App\Investments\Domain\BrokerSync\Client\BrokerClientInterface;

/**
 * Replaces the real broker clients in the test environment (config/services.yaml).
 */
final class FakeBrokerClientFactory implements BrokerClientFactoryInterface
{
    public FakeBrokerClient $client;

    public function __construct()
    {
        $this->client = new FakeBrokerClient();
    }

    #[\Override]
    public function forProvider(BrokerProvider $provider): BrokerClientInterface
    {
        return $this->client;
    }
}
