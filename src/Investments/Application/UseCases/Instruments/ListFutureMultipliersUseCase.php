<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Application\Response\DTO\Instruments\FutureMultiplierDto;
use App\Investments\Domain\Instruments\FutureRepositoryInterface;

final readonly class ListFutureMultipliersUseCase
{
    public function __construct(
        private FutureRepositoryInterface $futureRepository
    ) {
    }

    /**
     * @return array<int, FutureMultiplierDto>
     */
    public function execute(): array
    {
        $result = [];
        foreach ($this->futureRepository->findWithMultiplier() as $future) {
            $result[] = new FutureMultiplierDto(
                $future->getId() ?? 0,
                $future->getTicker(),
                $future->getMultiplier() ?? '',
            );
        }

        return $result;
    }
}
