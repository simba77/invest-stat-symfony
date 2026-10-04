<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;

final class DealsControllerTest extends ApiTestCase
{
    use CreatesInvestmentRecords;

    public function testSellRejectsOtherUsersAccount(): void
    {
        $other = $this->otherUser();
        $account = $this->createAccount($other);
        $deal = new Deal($other, $account, 'SBER', 'MOEX', DealStatus::Active, DealType::Long, 10, '300');
        $this->persist($deal);
        $this->loginAs($this->admin());

        $this->postJson('/api/deals/sell', ['id' => null, 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 10]);

        self::assertResponseStatusCodeSame(404);
        self::assertSame(DealStatus::Active, $this->findFresh(Deal::class, $deal->getId())?->getStatus());
    }
}
