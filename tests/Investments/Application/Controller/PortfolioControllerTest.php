<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;

final class PortfolioControllerTest extends ApiTestCase
{
    use CreatesInvestmentRecords;

    public function testShowsDealsInCurrenciesWithoutSymbolOfTheirOwn(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '100000');
        $this->createCurrencyRate('EUR', '90');
        $deal = new Deal($account, 'XS0000000001', 'MOEX', DealStatus::Active, DealType::Long, 2, '100');
        $deal->setInstrument($this->createBond('XS0000000001', 'EUR'));
        $this->persist($deal);
        $this->loginAs($admin);

        $this->getJson('/api/portfolio');

        self::assertResponseIsSuccessful();
        /** @var array{currencies: array<string, mixed>, dealsList: array<string, mixed>} $portfolio */
        $portfolio = $this->responseJson();
        self::assertSame(['EUR' => ['code' => 'EUR', 'name' => 'Euro']], $portfolio['currencies']);
        self::assertSame('€', $portfolio['dealsList']['active']['bonds']['EUR'][0]['groupData']['currency']);
    }
}
