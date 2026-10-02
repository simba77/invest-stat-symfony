<?php

declare(strict_types=1);

namespace App\Tests\Investments\BrokerSync;

use App\Investments\Domain\BrokerSync\Client\BrokerApiException;
use App\Investments\Domain\BrokerSync\Client\BrokerClientInterface;
use App\Investments\Domain\BrokerSync\Client\ExternalAccount;
use App\Investments\Domain\BrokerSync\Client\ExternalOperation;

/**
 * Plays the broker in kernel tests: returns what the test prepared and remembers the tokens it got.
 */
final class FakeBrokerClient implements BrokerClientInterface
{
    /** @var list<ExternalAccount> */
    public array $accounts = [];

    /** @var list<ExternalOperation> */
    public array $operations = [];

    /** @var list<string> */
    public array $receivedTokens = [];

    /** @var list<array{account: string, from: \DateTimeImmutable, to: \DateTimeImmutable}> */
    public array $operationRequests = [];

    public ?string $failure = null;

    #[\Override]
    public function getAccounts(string $token): array
    {
        $this->receive($token);

        return $this->accounts;
    }

    #[\Override]
    public function getOperations(string $token, string $accountId, \DateTimeImmutable $from, \DateTimeImmutable $to): iterable
    {
        $this->receive($token);
        $this->operationRequests[] = ['account' => $accountId, 'from' => $from, 'to' => $to];

        return $this->operations;
    }

    private function receive(string $token): void
    {
        $this->receivedTokens[] = $token;
        if ($this->failure !== null) {
            throw new BrokerApiException($this->failure);
        }
    }
}
