<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Domain\Instruments\Exceptions\InstrumentNotFoundException;
use App\Investments\Domain\Instruments\FutureMultiplier;
use App\Investments\Infrastructure\Persistence\Repository\FutureMultiplierRepository;

final readonly class CreateFutureMultiplierUseCase
{
    public function __construct(
        private FutureMultiplierRepository $futureMultiplierRepository
    ) {
    }

    public function execute(string $ticker, string $value): void
    {
        $futureMultiplier = $this->futureMultiplierRepository->findOneBy(['ticker' => $ticker]);
        if ($futureMultiplier) {
            throw new InstrumentNotFoundException(sprintf('Future multiplier with ticker %s already exists', $ticker));
        }

        $futureMultiplier = new FutureMultiplier($ticker, $value);
        $this->futureMultiplierRepository->save($futureMultiplier);
    }
}
