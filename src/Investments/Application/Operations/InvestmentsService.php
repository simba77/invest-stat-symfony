<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations;

use App\Investments\Application\Response\DTO\Operations\InvestmentResponseDTO;
use App\Investments\Infrastructure\Persistence\Repository\InvestmentRepository;
use App\Shared\Domain\User;

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
