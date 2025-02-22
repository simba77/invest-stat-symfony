<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use App\Investments\Application\Request\DTO\Operations\DealsFilterRequestDTO;
use App\Shared\Domain\User;

interface DealRepositoryInterface
{
    /**
     * @return array<int, Deal>
     */
    public function findByUserId(int $userId): array;

    /**
     * @return array<int, Deal>
     */
    public function getClosedDealsForUserByFilter(int $userId, ?DealsFilterRequestDTO $filter = null): array;

    /**
     * @return array<int, Deal>
     */
    public function findForUserAndAccount(int $userId, int $accountId): array;

    /**
     * @return array<int, Deal>
     */
    public function findForAccount(int $accountId): array;
}
