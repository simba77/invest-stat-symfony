<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Client;

/**
 * Read-only access to a broker account. Implementations throw {@see BrokerApiException}.
 */
interface BrokerClientInterface
{
    /**
     * @return list<ExternalAccount>
     */
    public function getAccounts(string $token): array;
}
