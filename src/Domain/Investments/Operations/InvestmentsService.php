<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations;

use App\Application\Response\DTO\Investments\Operations\InvestmentResponseDTO;
use App\Domain\Shared\User;
use App\Infrastructure\Persistence\Repository\InvestmentRepository;

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
