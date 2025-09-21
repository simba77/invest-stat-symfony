<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Application\Response\DTO\Instruments\FutureMultiplierDto;
use App\Investments\Infrastructure\Persistence\Repository\FutureMultiplierRepository;

final readonly class ListFutureMultipliersUseCase
{
    public function __construct(
        private FutureMultiplierRepository $futureMultiplierRepository
    ) {
    }

    /**
     * @return array<int, FutureMultiplierDto>
     */
    public function execute(): array
    {
        $futureMultipliers = $this->futureMultiplierRepository->findAll();

        $result = [];
        foreach ($futureMultipliers as $futureMultiplier) {
            $result[] = new FutureMultiplierDto(
                $futureMultiplier->getId() ?? 0,
                $futureMultiplier->getTicker(),
                $futureMultiplier->getValue(),
            );
        }

        return $result;
    }
}
