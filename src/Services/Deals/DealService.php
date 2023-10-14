<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Entity\Account;
use App\Entity\Deal;
use App\Entity\User;
use App\Request\DTO\Deals\CreateDealRequestDTO;
use App\Request\DTO\Deals\SellDealRequestDTO;
use App\Response\DTO\Securities\SecurityDTO;
use App\Services\AccountCalculator;
use App\Services\Deals\Exceptions\NoDealsException;
use App\Services\MarketData\Securities\SecuritiesService;
use App\Services\MarketData\Securities\SecurityTypeEnum;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class DealService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SecuritiesService $securitiesService,
        private readonly AccountCalculator $accountCalculator,
    ) {
    }

    public function addDeal(Account $account, User $user, CreateDealRequestDTO $dealRequestDTO): void
    {
        $deal = new Deal(
            $user,
            $account,
            $dealRequestDTO->ticker,
            $dealRequestDTO->stockMarket,
            DealStatus::Active,
            $dealRequestDTO->isShort ? DealType::Short : DealType::Long,
            $dealRequestDTO->quantity,
            $dealRequestDTO->buyPrice,
            $dealRequestDTO->targetPrice
        );

        $this->entityManager->persist($deal);

        $this->changeAccountBalanceWhenAddDeal($account, $dealRequestDTO);

        $this->accountCalculator->recalculateBalanceForAccount($account);

        $this->entityManager->flush();
    }

    public function sellOne(Deal $deal, SellDealRequestDTO $dto): void
    {
        $deal->setStatus(DealStatus::Closed);
        $deal->setSellPrice($dto->price);
        $this->entityManager->persist($deal);

        $this->changeAccountBalance($deal, $dto);

        $this->accountCalculator->recalculateBalanceForAccount($deal->getAccount());

        $this->entityManager->flush();
    }

    public function sellAsNeeded(User $user, Account $account, SellDealRequestDTO $dto): void
    {
        $needToSell = $dto->quantity;
        $deals = $this->entityManager->getRepository(Deal::class)
            ->findBy(
                [
                    'user' => $user,
                    'account' => $account,
                    'ticker' => $dto->ticker,
                    'status' => DealStatus::Active,
                ],
                ['id' => Criteria::ASC]
            );
        if (empty($deals)) {
            throw new NoDealsException('No Deals for this Ticker and Account');
        }

        foreach ($deals as $deal) {
            if ($deal->getQuantity() > $needToSell) {
                // Clone the deal and set the remaining quantity
                $additionalDeal = clone $deal;
                $additionalDeal->setQuantity($deal->getQuantity() - $needToSell);
                $this->entityManager->persist($additionalDeal);

                // Set the sell status and set the quantity
                $deal->setQuantity($needToSell);
                $deal->setStatus(DealStatus::Closed);
                $deal->setSellPrice($dto->price);
                $this->entityManager->persist($deal);
                $needToSell = 0;
                break;
            }

            // Set the sell status and reduce quantity that need to sell
            $deal->setStatus(DealStatus::Closed);
            $deal->setSellPrice($dto->price);
            $this->entityManager->persist($deal);
            $needToSell -= $deal->getQuantity();

            if ($needToSell === 0) {
                break;
            }
        }

        // If the number of securities in the database is less than the quantity need to sell.
        if ($needToSell > 0 && isset($deal)) {
            $additionalDeal = clone $deal;
            $additionalDeal->setQuantity($needToSell);
            $additionalDeal->setStatus(DealStatus::Closed);
            $additionalDeal->setSellPrice($dto->price);
            $this->entityManager->persist($additionalDeal);
        }

        if (isset($deal)) {
            $this->changeAccountBalance($deal, $dto);
        }

        $this->accountCalculator->recalculateBalanceForAccount($account);

        $this->entityManager->flush();
    }

    private function changeAccountBalanceWhenAddDeal(Account $account, CreateDealRequestDTO $dealRequestDTO): void
    {
        $security = $this->securitiesService->getSecurityByTickerAndStockMarket($dealRequestDTO->ticker, $dealRequestDTO->stockMarket);
        $dealSum = 0;
        $currency = 'RUB';

        if ($security) {
            if ($security->securityType === SecurityTypeEnum::Share) {
                $dealSum = $dealRequestDTO->buyPrice * $dealRequestDTO->quantity;
            } elseif ($security->securityType === SecurityTypeEnum::Bond) {
                $dealSum = ($security->lotSize * $dealRequestDTO->buyPrice / 100 * $dealRequestDTO->quantity) + ($security->bondAccumulatedCoupon * $dealRequestDTO->quantity);
            } elseif ($security->securityType === SecurityTypeEnum::Future) {
                // TODO: Change it
                $dealSum = 0;
            }

            if ($security->currency !== 'RUB') {
                $currency = $security->currency;
            }
        } else {
            // If no data on the security is found, then we set the expected data for calculating the price
            $dealSum = $dealRequestDTO->buyPrice * $dealRequestDTO->quantity;
            if ($dealRequestDTO->stockMarket !== 'MOEX') {
                $currency = 'USD';
            }
        }

        if ($currency === 'RUB') {
            $balanceToChange = $account->getBalance();

            // Depends on the type of deal we decide to increase or decrease the balance.
            if (! $dealRequestDTO->isShort) {
                $account->setBalance($balanceToChange - $dealSum);
            } else {
                $account->setBalance($balanceToChange + $dealSum);
            }
        } else {
            $balanceToChange = $account->getUsdBalance();

            // Depends on the type of deal we decide to increase or decrease the balance.
            if (! $dealRequestDTO->isShort) {
                $account->setUsdBalance($balanceToChange - $dealSum);
            } else {
                $account->setUsdBalance($balanceToChange + $dealSum);
            }
        }

        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    private function changeAccountBalance(Deal $deal, SellDealRequestDTO $dto): void
    {
        $security = $this->securitiesService->getSecurityByTickerAndStockMarket($deal->getTicker(), $deal->getStockMarket());
        if (! $security) {
            throw new RuntimeException('Security not found');
        }

        $dealSum = $this->getDealSum($security, $dto);
        $account = $deal->getAccount();
        if ($security->currency === 'RUB') {
            $balanceToChange = $account->getBalance();

            // Depends on the type of deal we decide to increase or decrease the balance.
            if ($deal->getType() === DealType::Long) {
                $account->setBalance($balanceToChange + $dealSum);
            } elseif ($deal->getType() === DealType::Short) {
                $account->setBalance($balanceToChange - $dealSum);
            }
        } else {
            $balanceToChange = $account->getUsdBalance();

            // Depends on the type of deal we decide to increase or decrease the balance.
            if ($deal->getType() === DealType::Long) {
                $account->setUsdBalance($balanceToChange + $dealSum);
            } elseif ($deal->getType() === DealType::Short) {
                $account->setUsdBalance($balanceToChange - $dealSum);
            }
        }

        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    private function getDealSum(SecurityDTO $securityDTO, SellDealRequestDTO $dealRequestDTO): float | int
    {
        if ($securityDTO->securityType === SecurityTypeEnum::Share) {
            return $dealRequestDTO->price * $dealRequestDTO->quantity;
        } elseif ($securityDTO->securityType === SecurityTypeEnum::Bond) {
            return ($securityDTO->lotSize * $dealRequestDTO->price / 100 * $dealRequestDTO->quantity) + ($securityDTO->bondAccumulatedCoupon * $dealRequestDTO->quantity);
        } elseif ($securityDTO->securityType === SecurityTypeEnum::Future) {
            // TODO: Change it
            return 0;
        }
        return 0;
    }
}
