<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain;

use App\Shared\Domain\TaxProfile;
use PHPUnit\Framework\TestCase;

final class TaxProfileTest extends TestCase
{
    /**
     * @dataProvider profiles
     */
    public function testDescribesTaxRate(TaxProfile $profile, string $ratePercent, bool $isTaxApplied): void
    {
        self::assertSame($ratePercent, $profile->ratePercent());
        self::assertSame($isTaxApplied, $profile->isTaxApplied());
    }

    /**
     * @return iterable<string, array{TaxProfile, string, bool}>
     */
    public static function profiles(): iterable
    {
        yield 'no tax' => [TaxProfile::None, '0', false];
        yield 'NDFL 13%' => [TaxProfile::Ndfl13, '13', true];
        yield 'NDFL 15%' => [TaxProfile::Ndfl15, '15', true];
    }
}
