<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Entity\Account;
use App\Entity\Deal;
use App\Entity\User;
use App\Request\DTO\Deals\SellDealRequestDTO;
use App\Response\DTO\Securities\SecurityDTO;
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
    ) {
    }

    public function sellOne(Deal $deal, SellDealRequestDTO $dto): void
    {
        $deal->setStatus(DealStatus::Closed);
        $deal->setSellPrice($dto->price);
        $this->entityManager->persist($deal);

        $this->changeAccountBalance($deal, $dto);

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
            // TODO: Change it
            return 0;
        } elseif ($securityDTO->securityType === SecurityTypeEnum::Future) {
            // TODO: Change it
            return 0;
        }
        return 0;
    }
}
