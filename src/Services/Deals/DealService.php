<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Entity\Account;
use App\Entity\Deal;
use App\Entity\User;
use App\Request\DTO\Deals\SellDealRequestDTO;
use App\Services\Deals\Exceptions\NoDealsException;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class DealService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function sellOne(Deal $deal, SellDealRequestDTO $dto): void
    {
        $deal->setStatus(DealStatus::Closed);
        $deal->setSellPrice($dto->price);
        $this->entityManager->persist($deal);
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

        $this->entityManager->flush();
    }
}
