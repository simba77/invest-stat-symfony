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
        $investments = $this->investmentRepository->findBy(['userId' => $user?->getId()], ['date' => 'DESC']);
        $result = [];
        foreach ($investments as $investment) {
            $result[] = new InvestmentResponseDTO(
                id:      $investment->getId(),
                sum:     $investment->getSum(),
                date:    $investment->getDate()->format('d.m.Y'),
                account: $investment->getAccount()?->getName()
            );
        }
        return $result;
    }
}
