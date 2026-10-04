<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Accounts\Account;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;

final class AccountsControllerTest extends ApiTestCase
{
    use CreatesInvestmentRecords;

    public function testDeleteIsNotReachableByGet(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->getJson('/api/accounts/delete/' . $account->getId());

        self::assertResponseStatusCodeSame(405);
        self::assertNotNull($this->findFresh(Account::class, $account->getId()));
    }
}
