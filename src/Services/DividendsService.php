<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\DividendRepository;
use App\Response\DTO\Dividends\DividendListItemDTO;
use Doctrine\Common\Collections\Criteria;

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
        $dividends = $this->dividendRepository->findBy(['user' => $user], ['date' => Criteria::DESC, 'id' => Criteria::DESC]);
        $result = [];
        foreach ($dividends as $dividend) {
            $result[] = new DividendListItemDTO(
                id:          $dividend->getId(),
                date:        $dividend->getDate()->format('d.m.Y'),
                ticker:      $dividend->getTicker(),
                amount:      $dividend->getAmount(),
                accountName: $dividend->getAccount()->getName()
            );
        }
        return $result;
    }
}
