<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Coupons;

use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Operations\CouponRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateCouponCommandHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly CouponRepositoryInterface $couponRepository,
    ) {
    }

    public function __invoke(UpdateCouponCommand $command): void
    {
        $account = $this->accountRepository->findById($command->accountId);
        $coupon = $this->couponRepository->findByIdAndUser($command->id, $command->user);

        if (! $account) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $command->accountId));
        }
        if (! $coupon) {
            throw new NotFoundException(sprintf('Coupon with id "%s" not found', $command->accountId));
        }

        $coupon->setDate(new \DateTimeImmutable($command->date));
        $coupon->setAmount($command->amount);
        $coupon->setTicker($command->ticker);
        $coupon->setStockMarket($command->stockMarket);
        $coupon->setAccount($account);

        $this->couponRepository->save($coupon);
    }
}
