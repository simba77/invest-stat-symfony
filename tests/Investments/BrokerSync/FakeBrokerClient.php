<?php

declare(strict_types=1);

namespace App\Tests\Investments\BrokerSync;

use App\Investments\Domain\BrokerSync\Client\BrokerApiException;
use App\Investments\Domain\BrokerSync\Client\BrokerClientInterface;
use App\Investments\Domain\BrokerSync\Client\ExternalAccount;

/**
 * Plays the broker in kernel tests: returns what the test prepared and remembers the tokens it got.
 */
final class FakeBrokerClient implements BrokerClientInterface
{
    /** @var list<ExternalAccount> */
    public array $accounts = [];

    /** @var list<string> */
    public array $receivedTokens = [];

    public ?string $failure = null;

    #[\Override]
    public function getAccounts(string $token): array
    {
        $this->receive($token);

        return $this->accounts;
    }

    private function receive(string $token): void
    {
        $this->receivedTokens[] = $token;
        if ($this->failure !== null) {
            throw new BrokerApiException($this->failure);
        }
    }
}
