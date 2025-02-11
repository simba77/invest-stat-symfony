<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Coupons;

use App\Investments\Domain\Accounts\AccountRepositoryInterface;
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

        $account = $this->accountRepository->findById($command->accountId);
        if (! $account) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $command->userId));
        }

        $coupon = new Coupon(
            user:        $user,
            account:     $account,
            ticker:      $command->ticker,
            stockMarket: $command->stockMarket,
            amount:      $command->amount,
            date:        new \DateTimeImmutable($command->date),
        );

        $this->couponRepository->save($coupon);
    }
}
