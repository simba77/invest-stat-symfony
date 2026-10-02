<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\BrokerSync;

/**
 * @psalm-api
 */
final readonly class BrokerSyncOptionDTO
{
    public function __construct(
        public string $code,
        public string $name,
    ) {
    }
}
