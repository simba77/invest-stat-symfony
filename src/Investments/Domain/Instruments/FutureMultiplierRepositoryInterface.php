<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

interface FutureMultiplierRepositoryInterface
{
    public function save(FutureMultiplier $futureMultiplier): void;

    public function findById(int $id): ?FutureMultiplier;

    public function findByTicker(string $ticker): ?FutureMultiplier;

    public function remove(FutureMultiplier $futureMultiplier): void;
}
