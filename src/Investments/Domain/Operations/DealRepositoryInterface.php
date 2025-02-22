<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use App\Investments\Application\Request\DTO\Operations\DealsFilterRequestDTO;
use App\Investments\Domain\Accounts\Account;
use App\Shared\Domain\User;

interface DealRepositoryInterface
{
    /**
     * @return array<int, array{
     *     deal: Deal,
     *     shareName: string,
     *     sharePrice: string,
     *     sharePrevPrice: string,
     *     shareCurrency: string,
     *     shareType: string,
     *     bondName: string,
     *     bondPrice: string,
     *     bondPrevPrice: string,
     *     bondCurrency: string,
     *     bondLotSize: string,
     *     futureName: string,
     *     futurePrice: string,
     *     futurePrevPrice: string,
     *     futureCurrency: string,
     *     futureStepPrice: string,
     *     futureLotSize: string,
     * }>
     */
    public function findForUser(?User $user): array;

    /**
     * @return array<int, array{
     *     deal: Deal,
     *     shareName: string,
     *     sharePrice: string,
     *     sharePrevPrice: string,
     *     shareCurrency: string,
     *     shareType: string,
     *     bondName: string,
     *     bondPrice: string,
     *     bondPrevPrice: string,
     *     bondCurrency: string,
     *     bondLotSize: string,
     *     futureName: string,
     *     futurePrice: string,
     *     futurePrevPrice: string,
     *     futureCurrency: string,
     *     futureStepPrice: string,
     *     futureLotSize: string,
     * }>
     */
    public function getClosedDealsForUserByFilter(User $user, ?DealsFilterRequestDTO $filter = null): array;

    /**
     * @return list<Deal>
     */
    public function findForUserAndAccount(int $userId, int $accountId): array;
}
