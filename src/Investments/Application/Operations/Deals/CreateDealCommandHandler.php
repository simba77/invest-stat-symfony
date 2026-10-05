<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Instruments\InstrumentRepositoryInterface;
use App\Investments\Domain\Instruments\Securities\SecuritiesService;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Shared\Domain\UserRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateDealCommandHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $dealRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly SecuritiesService $securitiesService,
        private readonly InstrumentRepositoryInterface $instrumentRepository,
        private readonly SyncedAccountGuard $syncedAccountGuard,
    ) {
    }

    public function __invoke(CreateDealCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);
        $account = $this->accountRepository->findById($command->accountId);
        if (! $user || ! $account) {
            throw new NotFoundException('No user or account found');
        }

        if ($account->getUserId() !== $command->userId) {
            throw new NotFoundException('The account does not belong to the user');
        }
        $this->syncedAccountGuard->assertManual($account);

        $deal = new Deal(
            user:        $user,
            account:     $account,
            ticker:      $command->ticker,
            stockMarket: $command->stockMarket,
            status:      DealStatus::Active,
            type:        $command->isShort ? DealType::Short : DealType::Long,
            quantity:    $command->quantity,
            buyPrice:    $command->buyPrice,
            targetPrice: $command->targetPrice,
        );

        $deal->setInstrument($this->instrumentRepository->findByTickerAndStockMarket($command->ticker, $command->stockMarket));

        $this->dealRepository->save($deal);

        $this->changeAccountBalanceWhenAddDeal($account, $command);

    }

    private function changeAccountBalanceWhenAddDeal(Account $account, CreateDealCommand $dealRequestDTO): void
    {
        $security = $this->securitiesService->getSecurityByTickerAndStockMarket($dealRequestDTO->ticker, $dealRequestDTO->stockMarket);
        $dealSum = '0';
        $currency = 'RUB';

        if ($security) {
            if ($security->securityType === SecurityTypeEnum::Share) {
                $dealSum = bcmul($dealRequestDTO->buyPrice, (string) $dealRequestDTO->quantity, 4);
            } elseif ($security->securityType === SecurityTypeEnum::Bond) {
                $dealSum = bcmul($security->lotSize, $dealRequestDTO->buyPrice, 4);
                $dealSum = bcdiv($dealSum, '100', 4);
                $dealSum = bcmul($dealSum, (string) $dealRequestDTO->quantity, 4);
                $dealSum = bcadd($dealSum, bcmul($security->bondAccumulatedCoupon, (string) $dealRequestDTO->quantity, 4), 4);
            }

            if ($security->currency !== 'RUB') {
                $currency = $security->currency;
            }
        } else {
            // If no data on the security is found, then we set the expected data for calculating the price
            $dealSum = bcmul($dealRequestDTO->buyPrice, (string) $dealRequestDTO->quantity, 4);
            if ($dealRequestDTO->stockMarket !== 'MOEX') {
                $currency = 'USD';
            }
        }

        if ($currency === 'RUB') {
            $balanceToChange = $account->getBalance();

            // Depends on the type of deal we decide to increase or decrease the balance.
            if (! $dealRequestDTO->isShort) {
                $account->setBalance(bcsub($balanceToChange, $dealSum, 4));
            } else {
                $account->setBalance(bcadd($balanceToChange, $dealSum, 4));
            }
        } else {
            $balanceToChange = $account->getUsdBalance();

            // Depends on the type of deal we decide to increase or decrease the balance.
            if (! $dealRequestDTO->isShort) {
                $account->setUsdBalance(bcsub($balanceToChange, $dealSum, 4));
            } else {
                $account->setUsdBalance(bcadd($balanceToChange, $dealSum, 4));
            }
        }

        $this->accountRepository->save($account);
    }
}
