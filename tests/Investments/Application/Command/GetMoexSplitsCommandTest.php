<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Command;

use App\Investments\Domain\Instruments\ShareSplit;
use App\Tests\Investments\Instruments\FakeShareSplitProvider;
use App\Tests\Support\InteractsWithDatabase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class GetMoexSplitsCommandTest extends KernelTestCase
{
    use InteractsWithDatabase;

    public function testAddsOnlyUnknownSplits(): void
    {
        $this->persist(new ShareSplit('T', 'MOEX', new \DateTimeImmutable('2026-04-17'), 1, 10));
        $command = new CommandTester((new Application(static::bootKernel()))->find('securities:get-moex-splits'));
        /** @var FakeShareSplitProvider $provider */
        $provider = static::getContainer()->get(FakeShareSplitProvider::class);
        $provider->splits = [
            new ShareSplit('T', 'MOEX', new \DateTimeImmutable('2026-04-17'), 1, 10),
            new ShareSplit('FXRU', 'MOEX', new \DateTimeImmutable('2018-12-12'), 10, 1),
        ];

        $command->execute([]);

        $command->assertCommandIsSuccessful();
        self::assertStringContainsString('New splits: 1', $command->getDisplay());
        $splits = $this->findFreshBy(ShareSplit::class, []);
        self::assertSame(['T', 'FXRU'], array_map(static fn (ShareSplit $split) => $split->getTicker(), $splits));
        self::assertSame(10, $splits[1]->getBefore());
        self::assertSame(1, $splits[1]->getAfter());
    }
}
