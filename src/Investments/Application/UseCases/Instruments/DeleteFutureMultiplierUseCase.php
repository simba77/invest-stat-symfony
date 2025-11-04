<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Domain\Instruments\Exceptions\FutureMultiplierNotFoundException;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;

final readonly class DeleteFutureMultiplierUseCase
{
    public function __construct(
        private FutureMultiplierRepositoryInterface $futureMultiplierRepository
    ) {
    }

    public function execute(int $id): void
    {
        $futureMultiplier = $this->futureMultiplierRepository->findById($id);
        if ($futureMultiplier === null) {
            throw new FutureMultiplierNotFoundException(sprintf('Future multiplier with id %s not found', $id));
        }

        $this->futureMultiplierRepository->remove($futureMultiplier);
    }
}
