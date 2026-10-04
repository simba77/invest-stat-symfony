<?php

declare(strict_types=1);

namespace App\Tests\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\FutureMultiplier;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Tests\Support\InteractsWithDatabase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FutureMultiplierRepositoryTest extends KernelTestCase
{
    use InteractsWithDatabase;

    public function testFindsMultipliersSavedAndRemovedAfterTheFirstLookup(): void
    {
        $repository = static::getContainer()->get(FutureMultiplierRepositoryInterface::class);
        $repository->save(new FutureMultiplier('SiZ6', '1'));
        self::assertNull($repository->findByTicker('RIZ6'));

        $multiplier = new FutureMultiplier('RIZ6', '2');
        $repository->save($multiplier);

        self::assertSame('2.0000', $this->valueOf($repository->findByTicker('RIZ6')));
        self::assertSame('1.0000', $this->valueOf($repository->findByTicker('SiZ6')));

        $repository->remove($multiplier);

        self::assertNull($repository->findByTicker('RIZ6'));
    }

    private function valueOf(?FutureMultiplier $multiplier): ?string
    {
        if ($multiplier === null) {
            return null;
        }

        return bcadd($multiplier->getValue(), '0', 4);
    }
}
