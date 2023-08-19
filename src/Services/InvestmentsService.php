<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\InvestmentRepository;
use App\Response\DTO\Investments\InvestmentResponseDTO;

class InvestmentsService
{
    public function __construct(
        private readonly InvestmentRepository $investmentRepository
    ) {
    }

    /**
     * @param User|null $user
     * @return InvestmentResponseDTO[]
     */
    public function getInvestmentsForUser(?User $user): array
    {
        $investments = $this->investmentRepository->getByUserId($user?->getId() ?? 0);
        $result = [];
        foreach ($investments as $investment) {
            $result[] = new InvestmentResponseDTO(
                id:      $investment['investment']->getId(),
                sum:     $investment['investment']->getSum(),
                date:    $investment['investment']->getDate()->format('d.m.Y'),
                account: $investment['account_name']
            );
        }
        return $result;
    }
}
