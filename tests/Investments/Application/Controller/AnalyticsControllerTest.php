<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;

final class AnalyticsControllerTest extends ApiTestCase
{
    use CreatesInvestmentRecords;

    public function testMonthlyProfitCountsDividendsInRoubles(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->createCurrencyRate('USD', '80');
        $this->createShare('AAPL', 'SPB', 'USD');
        $this->createShare('SBER');
        $this->createBond('RU000A10C8A4', 'CNY');
        $this->createDividend($account, 'AAPL', '2.5', '2024-03-10', 'SPB');
        $this->createDividend($account, 'SBER', '100', '2024-03-20');
        // A coupon of a yuan bond reached the account in roubles
        $this->createCoupon($account, 'RU000A10C8A4', '1000', '2024-04-05');
        $this->loginAs($admin);

        $this->getJson('/api/analytics/monthly-closed-deals');

        self::assertResponseIsSuccessful();
        self::assertSame(['profitByMonths' => ['2024.03' => '300.00', '2024.04' => '1000.0000']], $this->responseJson());
    }
}
