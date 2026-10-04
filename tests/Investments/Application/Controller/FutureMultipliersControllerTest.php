<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Instruments\Future;
use App\Tests\Support\ApiTestCase;

final class FutureMultipliersControllerTest extends ApiTestCase
{
    public function testListsFuturesWithMultiplier(): void
    {
        $si = $this->future('SiZ6', multiplier: '0.001');
        $this->future('RIZ6');
        $mx = $this->future('MXZ6', multiplier: '1');
        $this->loginAs($this->admin());

        $this->getJson('/api/futures/multipliers');

        self::assertResponseIsSuccessful();
        self::assertSame(
            [
                ['id' => $mx->getId(), 'ticker' => 'MXZ6', 'value' => '1.0000'],
                ['id' => $si->getId(), 'ticker' => 'SiZ6', 'value' => '0.0010'],
            ],
            $this->responseJson(),
        );
    }

    public function testCreateSetsMultiplierOfTheFuture(): void
    {
        $future = $this->future('SiZ6');
        $this->loginAs($this->admin());

        $this->postJson('/api/futures/multipliers/create', ['ticker' => 'SiZ6', 'value' => '0.001']);

        self::assertResponseIsSuccessful();
        self::assertSame('0.0010', $this->findFresh(Future::class, $future->getId())?->getMultiplier());
    }

    /**
     * @dataProvider refusedMultipliers
     */
    public function testCreateRefusesUnknownFutureAndSecondMultiplier(string $ticker, string $message): void
    {
        $this->future('SiZ6', multiplier: '0.001');
        $this->loginAs($this->admin());

        $this->postJson('/api/futures/multipliers/create', ['ticker' => $ticker, 'value' => '1']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['success' => false, 'message' => $message], $this->responseJson());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function refusedMultipliers(): iterable
    {
        yield 'unknown future' => ['UNKNOWN', 'Future with ticker UNKNOWN not found'];
        yield 'multiplier already set' => ['SiZ6', 'Future multiplier with ticker SiZ6 already exists'];
    }

    public function testDeleteResetsMultiplier(): void
    {
        $future = $this->future('SiZ6', multiplier: '0.001');
        $this->loginAs($this->admin());

        $this->client->request('DELETE', '/api/futures/multipliers/delete/' . $future->getId());

        self::assertResponseIsSuccessful();
        self::assertNull($this->findFresh(Future::class, $future->getId())?->getMultiplier());
        self::assertNotNull($this->findFresh(Future::class, $future->getId()));
    }

    private function future(string $ticker, ?string $multiplier = null): Future
    {
        $future = new Future($ticker, $ticker, 'MOEX', 'RUB', '100', stepPrice: '1');
        $future->setMultiplier($multiplier);
        $this->persist($future);

        return $future;
    }
}
