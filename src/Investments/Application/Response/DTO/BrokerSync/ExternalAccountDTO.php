<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\BrokerSync;

/**
 * @psalm-api
 */
final readonly class ExternalAccountDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $readOnly,
    ) {
    }
}
