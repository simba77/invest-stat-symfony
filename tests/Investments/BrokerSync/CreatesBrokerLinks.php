<?php

declare(strict_types=1);

namespace App\Tests\Investments\BrokerSync;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerProvider;
use App\Investments\Domain\BrokerSync\Client\ExternalAccount;
use App\Investments\Domain\BrokerSync\FeeAllocation;
use App\Investments\Domain\BrokerSync\TokenCipherInterface;
use App\Tests\Investments\CreatesInvestmentRecords;

/**
 * @psalm-require-extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 */
trait CreatesBrokerLinks
{
    use CreatesInvestmentRecords;

    private function linkAccount(
        Account $account,
        string $token = 'stored-token',
        string $externalAccountId = '2000',
        FeeAllocation $feeAllocation = FeeAllocation::PerOperation,
    ): BrokerAccountLink {
        $link = new BrokerAccountLink(
            account:                 $account,
            provider:                BrokerProvider::TInvest,
            encryptedToken:          $this->tokenCipher()->encrypt($token),
            externalAccountId:       $externalAccountId,
            externalAccountName:     'Broker account',
            externalAccountOpenedAt: new \DateTimeImmutable('2024-03-10 00:00:00'),
            feeAllocation:           $feeAllocation,
            enabled:                 true,
        );
        $this->persist($link);

        return $link;
    }

    private function brokerClient(): FakeBrokerClient
    {
        /** @var FakeBrokerClientFactory $factory */
        $factory = static::getContainer()->get(FakeBrokerClientFactory::class);

        return $factory->client;
    }

    private function brokerHasAccounts(ExternalAccount ...$accounts): void
    {
        $this->brokerClient()->accounts = array_values($accounts);
    }

    private function tokenCipher(): TokenCipherInterface
    {
        /** @var TokenCipherInterface */
        return static::getContainer()->get(TokenCipherInterface::class);
    }
}
