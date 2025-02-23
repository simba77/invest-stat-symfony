<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Application\Request\DTO\Operations\EditDealRequestDTO;
use App\Investments\Application\Request\DTO\Operations\SellDealRequestDTO;
use App\Investments\Application\Response\DTO\Instruments\SecurityDTO;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Securities\SecuritiesService;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Investments\Domain\Operations\Deals\Exceptions\NoDealsException;
use App\Shared\Domain\User;
use Carbon\Carbon;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class DealService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SecuritiesService $securitiesService,
        private readonly AccountBalanceCalculator $accountBalanceCalculator,
    ) {
    }

    public function sellOne(Deal $deal, SellDealRequestDTO $dto): void
    {
        $deal->setStatus(DealStatus::Closed);
        $deal->setSellPrice($dto->price);
        $deal->setClosingDate(Carbon::now());
        $this->entityManager->persist($deal);

        $this->changeAccountBalance($deal, $dto);

        $this->accountBalanceCalculator->recalculateBalance($deal->getAccount());

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
                $deal->setClosingDate(Carbon::now());
                $deal->setSellPrice($dto->price);
                $this->entityManager->persist($deal);
                $needToSell = 0;
                break;
            }

            // Set the sell status and reduce quantity that need to sell
            $deal->setStatus(DealStatus::Closed);
            $deal->setClosingDate(Carbon::now());
            $deal->setSellPrice($dto->price);
            $this->entityManager->persist($deal);
            $needToSell -= $deal->getQuantity();

            if ($needToSell === 0) {
                break;
            }
        }

        // If the number of securities in the database is less than the quantity need to sell.
        if ($needToSell > 0) {
            $additionalDeal = clone $deal;
            $additionalDeal->setQuantity($needToSell);
            $additionalDeal->setStatus(DealStatus::Closed);
            $additionalDeal->setClosingDate(Carbon::now());
            $additionalDeal->setSellPrice($dto->price);
            $this->entityManager->persist($additionalDeal);
        }


        $this->changeAccountBalance($deal, $dto);
        $this->accountBalanceCalculator->recalculateBalance($account);

        $this->entityManager->flush();
    }

    private function changeAccountBalance(Deal $deal, SellDealRequestDTO $dto): void
    {
        $security = $this->securitiesService->getSecurityByTickerAndStockMarket($deal->getTicker(), $deal->getStockMarket());
        if (! $security) {
            throw new RuntimeException('Security not found');
        }

        $dealSum = $this->getDealSum($security, $dto, $deal);
        $account = $deal->getAccount();
        if ($security->currency === 'RUB') {
            $balanceToChange = $account->getBalance();

            // Depends on the type of deal we decide to increase or decrease the balance.
            if ($deal->getType() === DealType::Long) {
                $account->setBalance(bcadd($balanceToChange, $dealSum, 4));
            } elseif ($deal->getType() === DealType::Short) {
                $account->setBalance(bcsub($balanceToChange, $dealSum, 4));
            }
        } else {
            $balanceToChange = $account->getUsdBalance();

            // Depends on the type of deal we decide to increase or decrease the balance.
            if ($deal->getType() === DealType::Long) {
                $account->setUsdBalance(bcadd($balanceToChange, $dealSum, 4));
            } elseif ($deal->getType() === DealType::Short) {
                $account->setUsdBalance(bcsub($balanceToChange, $dealSum, 4));
            }
        }

        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    private function getDealSum(SecurityDTO $securityDTO, SellDealRequestDTO $dealRequestDTO, Deal $deal): string
    {
        if ($securityDTO->securityType === SecurityTypeEnum::Share) {
            return bcmul($dealRequestDTO->price, (string) $dealRequestDTO->quantity, 4);
        } elseif ($securityDTO->securityType === SecurityTypeEnum::Bond) {
            $result = bcmul($securityDTO->lotSize, $dealRequestDTO->price, 4);
            $result = bcdiv($result, '100', 4);
            $result = bcmul($result, (string) $dealRequestDTO->quantity, 4);
            return bcadd($result, bcmul($securityDTO->bondAccumulatedCoupon, (string) $dealRequestDTO->quantity, 4), 4);
        } elseif ($securityDTO->securityType === SecurityTypeEnum::Future) {
            if ($securityDTO->lotSize < $dealRequestDTO->price) {
                $sellLotPrice = $dealRequestDTO->price;
                $buyLotPrice = $deal->getBuyPrice();
            } else {
                $sellLotPrice = bcmul($securityDTO->lotSize, $dealRequestDTO->price, 4);
                $buyLotPrice = bcmul($deal->getBuyPrice(), $securityDTO->lotSize, 4);
            }
            $sellFullPrice = bcmul($sellLotPrice, (string) $dealRequestDTO->quantity, 4);
            $buyFullPrice = bcmul($buyLotPrice, (string) $dealRequestDTO->quantity, 4);
            return bcsub($sellFullPrice, $buyFullPrice);
        }

        // @phpstan-ignore-next-line
        return '0';
    }

    public function changeDeal(Deal $deal, EditDealRequestDTO $dto): void
    {
        $deal->setTicker($dto->ticker);
        $deal->setStockMarket($dto->stockMarket);
        $deal->setType($dto->isShort ? DealType::Short : DealType::Long);
        $deal->setQuantity($dto->quantity);
        $deal->setBuyPrice($dto->buyPrice);
        $deal->setTargetPrice($dto->targetPrice);
        $this->entityManager->persist($deal);

        $this->accountBalanceCalculator->recalculateBalance($deal->getAccount());

        $this->entityManager->flush();
    }
}
