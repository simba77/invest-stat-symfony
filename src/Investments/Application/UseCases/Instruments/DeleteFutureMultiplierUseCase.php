<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Domain\Instruments\Exceptions\FutureMultiplierNotFoundException;
use App\Investments\Domain\Instruments\FutureRepositoryInterface;

final readonly class DeleteFutureMultiplierUseCase
{
    public function __construct(
        private FutureRepositoryInterface $futureRepository
    ) {
    }

    /**
     * @param int $id the id of the future
     */
    public function execute(int $id): void
    {
        $future = $this->futureRepository->findById($id);
        if ($future?->getMultiplier() === null) {
            throw new FutureMultiplierNotFoundException(sprintf('Future multiplier with id %s not found', $id));
        }

        $future->setMultiplier(null);
        $this->futureRepository->save($future);
    }
}
