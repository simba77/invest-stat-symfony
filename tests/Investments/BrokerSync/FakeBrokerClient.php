<?php

declare(strict_types=1);

namespace App\Tests\Investments\BrokerSync;

use App\Investments\Domain\BrokerSync\Client\BrokerApiException;
use App\Investments\Domain\BrokerSync\Client\BrokerClientInterface;
use App\Investments\Domain\BrokerSync\Client\ExternalAccount;
use App\Investments\Domain\BrokerSync\Client\ExternalInstrument;
use App\Investments\Domain\BrokerSync\Client\ExternalOperation;
use App\Investments\Domain\BrokerSync\Client\ExternalPositions;

/**
 * Plays the broker in kernel tests: returns what the test prepared and remembers the tokens it got.
 */
final class FakeBrokerClient implements BrokerClientInterface
{
    /** @var list<ExternalAccount> */
    public array $accounts = [];

    /** @var list<ExternalOperation> */
    public array $operations = [];

    public ExternalPositions $positions;

    /** @var array<string, ExternalInstrument> by uid */
    public array $instruments = [];

    /** @var list<string> */
    public array $receivedTokens = [];

    /** @var list<array{account: string, from: \DateTimeImmutable, to: \DateTimeImmutable}> */
    public array $operationRequests = [];

    public ?string $failure = null;

    /** @var (\Closure(): void)|null called when positions are asked for, e.g. to break the database meanwhile */
    public ?\Closure $onPositions = null;

    public function __construct()
    {
        $this->positions = new ExternalPositions([], []);
    }

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

    #[\Override]
    public function getPositions(string $token, string $accountId): ExternalPositions
    {
        $this->receive($token);
        if ($this->onPositions !== null) {
            ($this->onPositions)();
        }

        return $this->positions;
    }

    #[\Override]
    public function findInstrument(string $token, string $instrumentUid): ?ExternalInstrument
    {
        $this->receive($token);

        return $this->instruments[$instrumentUid] ?? null;
    }

    private function receive(string $token): void
    {
        $this->receivedTokens[] = $token;
        if ($this->failure !== null) {
            throw new BrokerApiException($this->failure);
        }
    }
}
