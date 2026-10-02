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

    /**
     * Operations of every state, so that a later sync sees a pending operation executed or canceled.
     *
     * @return iterable<ExternalOperation>
     */
    public function getOperations(string $token, string $accountId, \DateTimeImmutable $from, \DateTimeImmutable $to): iterable;
}
