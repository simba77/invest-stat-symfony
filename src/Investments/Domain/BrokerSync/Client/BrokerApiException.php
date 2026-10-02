<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Client;

/**
 * The broker API is unreachable or rejected the request.
 */
final class BrokerApiException extends \RuntimeException
{
}
