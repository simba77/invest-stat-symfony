<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Pagination;

use App\Shared\Application\Pagination\PageRequestFactory;
use PHPUnit\Framework\TestCase;

final class PageRequestFactoryTest extends TestCase
{
    /**
     * @dataProvider requests
     *
     * @param array{int, int, int} $requested page, per page, total items
     * @param array{int, int, int} $expected page, per page, offset
     */
    public function testNormalizesRequestedPage(array $requested, array $expected): void
    {
        $pageRequest = (new PageRequestFactory())->create(...$requested);

        self::assertSame($expected, [$pageRequest->page, $pageRequest->perPage, $pageRequest->offset]);
    }

    /**
     * @return iterable<string, array{array{int, int, int}, array{int, int, int}}>
     */
    public static function requests(): iterable
    {
        yield 'first page' => [[1, 20, 45], [1, 20, 0]];
        yield 'middle page' => [[2, 20, 45], [2, 20, 20]];
        yield 'last page' => [[3, 20, 45], [3, 20, 40]];
        yield 'page past the last one' => [[7, 20, 45], [3, 20, 40]];
        yield 'page below the first one' => [[-3, 20, 45], [1, 20, 0]];
        yield 'no items' => [[5, 20, 0], [1, 20, 0]];
        yield 'page size above the maximum' => [[1, 1000, 45], [1, 100, 0]];
        yield 'zero page size' => [[2, 0, 45], [2, 20, 20]];
        yield 'negative page size' => [[1, -5, 45], [1, 20, 0]];
    }
}
