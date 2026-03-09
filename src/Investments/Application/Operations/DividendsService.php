<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations;

use App\Investments\Application\Response\DTO\Operations\DividendListItemDTO;
use App\Investments\Infrastructure\Persistence\Repository\DividendRepository;
use App\Shared\Domain\User;
use Doctrine\Common\Collections\Order;

class DividendsService
{
    public function __construct(
        private readonly DividendRepository $dividendRepository
    ) {
    }

    /**
     * @param User|null $user
     * @return DividendListItemDTO[]
     */
    public function getDividendsForUser(?User $user): array
    {
        $dividends = $this->dividendRepository->findBy(['user' => $user], ['date' => Order::Descending->value, 'id' => Order::Descending->value]);
        $result = [];
        foreach ($dividends as $dividend) {
            $result[] = new DividendListItemDTO(
                id:          $dividend->getId(),
                date:        $dividend->getDate()->format('d.m.Y'),
                ticker:      $dividend->getTicker(),
                stockMarket: $dividend->getStockMarket(),
                amount:      $dividend->getAmount(),
                tax:         $dividend->getTax(),
                accountName: $dividend->getAccount()->getName()
            );
        }
        return $result;
    }
}
