<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Coupons;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Domain\Operations\CouponRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteCouponCommandHandler
{
    public function __construct(
        public readonly CouponRepositoryInterface $couponRepository,
        private readonly SyncedAccountGuard $syncedAccountGuard,
        private readonly ManualJournal $journal,
    ) {
    }

    public function __invoke(DeleteCouponCommand $command): void
    {
        $coupon = $this->couponRepository->findById($command->id);
        if (! $coupon) {
            throw new NotFoundException(sprintf('Coupon with id "%s" not found', $command->id));
        }
        $this->syncedAccountGuard->assertManual($coupon->getAccount());
        $this->journal->changeRecords(fn () => $this->couponRepository->remove($coupon), $coupon->getAccount());
    }
}
