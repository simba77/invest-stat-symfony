<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Coupons;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Instruments\BondRepositoryInterface;
use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Operations\CouponRepositoryInterface;
use App\Shared\Domain\UserRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateCouponCommandHandler
{
    public function __construct(
        private readonly CouponRepositoryInterface $couponRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly SyncedAccountGuard $syncedAccountGuard,
        private readonly ManualJournal $journal,
        private readonly BondRepositoryInterface $bondRepository,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function __invoke(CreateCouponCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);
        if (! $user) {
            throw new NotFoundException(sprintf('User with id "%s" not found', $command->userId));
        }

        $account = $this->accountRepository->getByIdAndUser($command->accountId, $user);
        if (! $account) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $command->accountId));
        }

        $this->syncedAccountGuard->assertManual($account);

        $coupon = new Coupon(
            account:     $account,
            ticker:      $command->ticker,
            stockMarket: $command->stockMarket,
            amount:      $command->amount,
            date:        new \DateTimeImmutable($command->date),
        );
        $coupon->setBond($this->bondRepository->findByTickerAndStockMarket($command->ticker, $command->stockMarket));

        $this->journal->changeRecords(fn () => $this->couponRepository->save($coupon), $account);
    }
}
