<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Client;

final readonly class ExternalAccount
{
    public function __construct(
        public string $id,
        public string $name,
        public ?\DateTimeImmutable $openedAt,
        public bool $readOnly,
    ) {
    }
}
