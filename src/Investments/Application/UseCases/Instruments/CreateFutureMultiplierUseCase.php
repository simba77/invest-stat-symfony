<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Domain\Instruments\Exceptions\InstrumentNotFoundException;
use App\Investments\Domain\Instruments\FutureRepositoryInterface;

final readonly class CreateFutureMultiplierUseCase
{
    public function __construct(
        private FutureRepositoryInterface $futureRepository
    ) {
    }

    /**
     * @param numeric-string $value
     */
    public function execute(string $ticker, string $value): void
    {
        $future = $this->futureRepository->findByTicker($ticker);
        if ($future === null) {
            throw new InstrumentNotFoundException(sprintf('Future with ticker %s not found', $ticker));
        }
        if ($future->getMultiplier() !== null) {
            throw new InstrumentNotFoundException(sprintf('Future multiplier with ticker %s already exists', $ticker));
        }

        $future->setMultiplier($value);
        $this->futureRepository->save($future);
    }
}
