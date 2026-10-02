<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Client;

use App\Investments\Domain\BrokerSync\BrokerProvider;

interface BrokerClientFactoryInterface
{
    public function forProvider(BrokerProvider $provider): BrokerClientInterface;
}
