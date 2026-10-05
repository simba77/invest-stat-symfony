<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Journal\ManualOperationType;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * The operations list shows the journal of a manual account, the latest operations first; a sale,
 * a block or a cash operation can be cancelled and a sale corrected, as long as the rest of the
 * journal still finds its lots.
 */
final class AccountOperationsTest extends ApiTestCase
{
    use ClockSensitiveTrait;
    use CreatesInvestmentRecords;

    public function testListsJournalLatestFirst(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '10000');
        $account->setCommission('0.1');
        $this->persist($account);
        $this->createShare('SBER', price: '300');
        $this->loginAs($admin);

        static::mockTime('2026-03-02 09:00:00');
        $this->postJson('/api/deals/create/' . $account->getId(), ['ticker' => 'SBER', 'stockMarket' => 'MOEX', 'quantity' => 10, 'buyPrice' => '250', 'targetPrice' => '0', 'isShort' => false]);
        $deal = $this->findFreshBy(Deal::class, ['account' => $account->getId()])[0];
        static::mockTime('2026-03-03 10:00:00');
        $this->postJson('/api/deals/block/' . $deal->getId());
        static::mockTime('2026-03-04 11:00:00');
        $this->postJson('/api/deals/unblock/' . $deal->getId());
        static::mockTime('2026-03-05 12:00:00');
        $this->postJson('/api/deals/sell', ['id' => $deal->getId(), 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 10]);
        static::mockTime('2026-03-06 13:00:00');
        $this->postJson('/api/accounts/update/' . $account->getId(), [
            'name' => 'Broker', 'balance' => '5000', 'blockedBalance' => '1000', 'usdBalance' => '0', 'blockedUsdBalance' => '0',
            'commission' => '0.1', 'futuresCommission' => '0', 'sort' => 100,
        ]);
        self::assertResponseIsSuccessful();

        $this->getJson('/api/accounts/' . $account->getId() . '/operations?perPage=4');

        self::assertResponseIsSuccessful();
        /** @var array{items: list<array<string, mixed>>, pagination: array<string, mixed>} $page */
        $page = $this->responseJson();
        self::assertSame(['page' => 1, 'perPage' => 4, 'totalItems' => 7, 'totalPages' => 2, 'hasPrev' => false, 'hasNext' => true], $page['pagination']);
        self::assertSame([
            ['block_cash', '2026-03-06T13:00:00+00:00', null, null, null, null, null, '1000.0000', 'RUB', null, null],
            ['cash_adjustment', '2026-03-06T13:00:00+00:00', null, null, null, null, null, '-5594.4000', 'RUB', null, null],
            ['close', '2026-03-05T12:00:00+00:00', 'SBER', 'share', 10, '310.0000', '3.1000', null, 'RUB', '2026-03-02T09:00:00+00:00', '250.0000'],
            ['unblock', '2026-03-04T11:00:00+00:00', 'SBER', 'share', null, null, null, null, 'RUB', '2026-03-02T09:00:00+00:00', '250.0000'],
        ], array_map(self::describe(...), $page['items']));
        self::assertSame([[true, false], [true, false], [true, true], [true, false]], array_map(static fn (array $item) => [$item['canCancel'], $item['canEdit']], $page['items']));

        $this->getJson('/api/accounts/' . $account->getId() . '/operations?perPage=4&page=2');

        /** @var array{items: list<array<string, mixed>>} $page */
        $page = $this->responseJson();
        self::assertSame([
            ['block', '2026-03-03T10:00:00+00:00', 'SBER', 'share', null, null, null, null, 'RUB', '2026-03-02T09:00:00+00:00', '250.0000'],
            ['buy', '2026-03-02T09:00:00+00:00', 'SBER', 'share', 10, '250.0000', '2.5000', null, 'RUB', null, null],
            ['cash_adjustment', '2026-03-02T09:00:00+00:00', null, null, null, null, null, '10000.0000', 'RUB', null, null],
        ], array_map(self::describe(...), $page['items']));
        self::assertSame([true, false, true], array_column($page['items'], 'canCancel'));
    }

    public function testDoesNotListJournalOfAnotherUser(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->loginAs($this->admin());

        $this->getJson('/api/accounts/' . $account->getId() . '/operations');

        self::assertResponseStatusCodeSame(404);
    }

    public function testCancelOfSaleReopensTheDeal(): void
    {
        $account = $this->accountWithPurchase();
        $deal = $this->deals($account)[0];
        $this->sellAt('2026-03-05 12:00:00', $account, $deal, 10);
        $sale = $this->operation($account, ManualOperationType::Close);

        $this->postJson($this->operationUri($account, $sale, 'cancel'));

        self::assertResponseIsSuccessful();
        self::assertSame([[DealStatus::Active, 10, null]], $this->describeDeals($account));
        self::assertSame('7497.5000', $this->findFresh(Account::class, $account->getId())?->getBalance());
    }

    public function testCancelOfBlockUnblocksTheDeal(): void
    {
        $account = $this->accountWithPurchase();
        $this->postJson('/api/deals/block/' . $this->deals($account)[0]->getId());

        $this->postJson($this->operationUri($account, $this->operation($account, ManualOperationType::Block), 'cancel'));

        self::assertResponseIsSuccessful();
        self::assertSame([[DealStatus::Active, 10, null]], $this->describeDeals($account));
    }

    public function testCancelOfCashAdjustmentTakesItsMoneyBack(): void
    {
        $account = $this->accountWithPurchase();
        $this->postJson('/api/accounts/update/' . $account->getId(), $this->accountForm(balance: '8000'));

        $adjustments = $this->findFreshBy(ManualOperation::class, ['account' => $account->getId(), 'type' => ManualOperationType::CashAdjustment]);
        $this->postJson($this->operationUri($account, end($adjustments), 'cancel'));

        self::assertResponseIsSuccessful();
        self::assertSame('7497.5000', $this->findFresh(Account::class, $account->getId())?->getBalance());
    }

    public function testRefusesToCancelPurchase(): void
    {
        $account = $this->accountWithPurchase();

        $this->postJson($this->operationUri($account, $this->operation($account, ManualOperationType::Buy), 'cancel'));

        self::assertResponseStatusCodeSame(409);
        self::assertSame([[DealStatus::Active, 10, null]], $this->describeDeals($account));
    }

    public function testRefusesToCancelSaleWhoseRestWasSoldLater(): void
    {
        $account = $this->accountWithPurchase();
        static::mockTime('2026-03-05 12:00:00');
        $this->postJson('/api/deals/sell', ['id' => null, 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 4]);
        $firstSale = $this->operation($account, ManualOperationType::Close);
        $rest = array_values(array_filter($this->deals($account), static fn (Deal $deal) => $deal->getStatus() === DealStatus::Active))[0];
        $this->sellAt('2026-03-06 12:00:00', $account, $rest, 6);

        $this->postJson($this->operationUri($account, $firstSale, 'cancel'));

        self::assertResponseStatusCodeSame(409);
        /** @var array{message: string} $error */
        $error = $this->responseJson();
        self::assertStringStartsWith('The change would break the journal: SBER: 6 of 6 securities were not open to be closed', $error['message']);
        self::assertCount(2, $this->findFreshBy(ManualOperation::class, ['account' => $account->getId(), 'type' => ManualOperationType::Close]));
    }

    public function testRefusesToFreeMoreCashThanWasBlocked(): void
    {
        $account = $this->accountWithPurchase();
        $this->postJson('/api/accounts/update/' . $account->getId(), $this->accountForm(blockedBalance: '1000'));
        $block = $this->operation($account, ManualOperationType::BlockCash);
        $this->postJson('/api/accounts/update/' . $account->getId(), $this->accountForm(blockedBalance: '400'));

        $this->postJson($this->operationUri($account, $block, 'cancel'));

        self::assertResponseStatusCodeSame(409);
        /** @var array{message: string} $error */
        $error = $this->responseJson();
        self::assertSame('The change would break the journal: more RUB would be freed than was blocked', $error['message']);
        self::assertSame('400.0000', $this->findFresh(Account::class, $account->getId())?->getBlockedCash('RUB'));
    }

    public function testCorrectsPriceDateAndCommissionOfSale(): void
    {
        $account = $this->accountWithPurchase();
        $deal = $this->deals($account)[0];
        $this->sellAt('2026-03-05 12:00:00', $account, $deal, 10);
        $sale = $this->operation($account, ManualOperationType::Close);

        $this->postJson($this->operationUri($account, $sale, 'edit'), ['price' => '300', 'executedAt' => '2026-03-04T13:00:00+03:00']);

        self::assertResponseIsSuccessful();
        $sale = $this->findFresh(ManualOperation::class, $sale->getId());
        self::assertSame(['300.0000', '2026-03-04 10:00:00', '3.0000'], [$sale?->getPrice(), $sale?->getExecutedAt()->format('Y-m-d H:i:s'), $sale?->getCommission()]);
        self::assertSame([[DealStatus::Closed, 10, '2026-03-04 10:00:00']], $this->describeDeals($account));
        // 7497.50 after the purchase, then 3000 for the securities less 3.00 of commission
        self::assertSame('10494.5000', $this->findFresh(Account::class, $account->getId())?->getBalance());
    }

    public function testRefusesToMoveSaleAfterBlockOfItsRest(): void
    {
        $account = $this->accountWithPurchase();
        static::mockTime('2026-03-05 12:00:00');
        $this->postJson('/api/deals/sell', ['id' => null, 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 4]);
        $sale = $this->operation($account, ManualOperationType::Close);
        $rest = array_values(array_filter($this->deals($account), static fn (Deal $deal) => $deal->getStatus() === DealStatus::Active))[0];
        static::mockTime('2026-03-06 12:00:00');
        $this->postJson('/api/deals/block/' . $rest->getId());
        static::mockTime('2026-03-10 12:00:00');

        $this->postJson($this->operationUri($account, $sale, 'edit'), ['price' => '310', 'executedAt' => '2026-03-07T12:00:00+00:00']);

        self::assertResponseStatusCodeSame(409);
        /** @var array{message: string} $error */
        $error = $this->responseJson();
        self::assertMatchesRegularExpression('/^The change would break the journal: Block of lot op:\d+#1 that is not open$/', $error['message']);
        self::assertSame('2026-03-05 12:00:00', $this->findFresh(ManualOperation::class, $sale->getId())?->getExecutedAt()->format('Y-m-d H:i:s'));
    }

    public function testRefusesToDateSaleInFuture(): void
    {
        $account = $this->accountWithPurchase();
        $this->sellAt('2026-03-05 12:00:00', $account, $this->deals($account)[0], 10);

        $this->postJson($this->operationUri($account, $this->operation($account, ManualOperationType::Close), 'edit'), ['price' => '310', 'executedAt' => '2026-03-06T12:00:00+00:00']);

        self::assertResponseStatusCodeSame(409);
        /** @var array{message: string} $error */
        $error = $this->responseJson();
        self::assertSame('The sale cannot be dated in the future', $error['message']);
    }

    public function testRefusesToCorrectOtherOperationsAsSale(): void
    {
        $account = $this->accountWithPurchase();

        $this->postJson($this->operationUri($account, $this->operation($account, ManualOperationType::CashAdjustment), 'edit'), ['price' => '310', 'executedAt' => '2026-03-02T12:00:00+00:00']);

        self::assertResponseStatusCodeSame(409);
        /** @var array{message: string} $error */
        $error = $this->responseJson();
        self::assertSame('Only the price and the date of a sale can be corrected', $error['message']);
    }

    public function testRejectsSaleCorrectionWithoutDate(): void
    {
        $account = $this->accountWithPurchase();
        $this->sellAt('2026-03-05 12:00:00', $account, $this->deals($account)[0], 10);

        $this->postJson($this->operationUri($account, $this->operation($account, ManualOperationType::Close), 'edit'), ['price' => '310', 'executedAt' => '05.03.2026']);

        $this->assertViolatedFields(['executedAt']);
    }

    public function testDoesNotCancelOperationOfAnotherUser(): void
    {
        $account = $this->accountWithPurchase();
        $operation = $this->operation($account, ManualOperationType::CashAdjustment);
        $other = $this->createAccount($this->otherUser());
        $this->loginAs($this->otherUser());

        $this->postJson($this->operationUri($account, $operation, 'cancel'));
        self::assertResponseStatusCodeSame(404);
        $this->postJson($this->operationUri($other, $operation, 'cancel'));
        self::assertResponseStatusCodeSame(404);

        self::assertNotNull($this->findFresh(ManualOperation::class, $operation->getId()));
    }

    /**
     * A manual account of the admin with 10 000 roubles and the commission of 0.1% that bought
     * 10 SBER at 250 on 2026-03-02; the admin is logged in.
     */
    private function accountWithPurchase(): Account
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '10000');
        $account->setCommission('0.1');
        $this->persist($account);
        $this->createShare('SBER', price: '300');
        $this->loginAs($admin);

        static::mockTime('2026-03-02 09:00:00');
        $this->postJson('/api/deals/create/' . $account->getId(), ['ticker' => 'SBER', 'stockMarket' => 'MOEX', 'quantity' => 10, 'buyPrice' => '250', 'targetPrice' => '0', 'isShort' => false]);
        self::assertResponseIsSuccessful();
        static::mockTime('2026-03-05 18:00:00');

        return $account;
    }

    private function sellAt(string $at, Account $account, Deal $deal, int $quantity): void
    {
        static::mockTime($at);
        $this->postJson('/api/deals/sell', ['id' => $deal->getId(), 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => $quantity]);
        self::assertResponseIsSuccessful();
        static::mockTime('2026-03-05 18:00:00');
    }

    /**
     * @return array<string, mixed>
     */
    private function accountForm(string $balance = '7497.5', string $blockedBalance = '0'): array
    {
        return [
            'name' => 'Broker', 'balance' => $balance, 'blockedBalance' => $blockedBalance, 'usdBalance' => '0', 'blockedUsdBalance' => '0',
            'commission' => '0.1', 'futuresCommission' => '0', 'sort' => 100,
        ];
    }

    /**
     * @return list<Deal>
     */
    private function deals(Account $account): array
    {
        return $this->findFreshBy(Deal::class, ['account' => $account->getId()]);
    }

    /**
     * @return list<list<mixed>>
     */
    private function describeDeals(Account $account): array
    {
        return array_map(
            static fn (Deal $deal) => [$deal->getStatus(), $deal->getQuantity(), $deal->getClosingDate()?->format('Y-m-d H:i:s')],
            $this->deals($account),
        );
    }

    /**
     * The first operation of the type in the journal of the account.
     */
    private function operation(Account $account, ManualOperationType $type): ManualOperation
    {
        $operations = $this->findFreshBy(ManualOperation::class, ['account' => $account->getId(), 'type' => $type]);
        self::assertNotEmpty($operations);

        return $operations[0];
    }

    private function operationUri(Account $account, ManualOperation|false $operation, string $action): string
    {
        self::assertInstanceOf(ManualOperation::class, $operation);

        return sprintf('/api/accounts/%d/operations/%d/%s', (int) $account->getId(), (int) $operation->getId(), $action);
    }

    /**
     * @param array<string, mixed> $item
     * @return list<mixed>
     */
    private static function describe(array $item): array
    {
        return [
            $item['type'], $item['executedAt'], $item['ticker'], $item['instrumentType'], $item['quantity'], $item['price'], $item['commission'],
            $item['amount'], $item['currency'], $item['lotOpenedAt'], $item['lotPrice'],
        ];
    }
}
