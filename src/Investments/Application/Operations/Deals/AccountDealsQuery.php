<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\Response\DTO\Accounts\AccountDealsResponseDTO;
use App\Shared\Domain\Bus\QueryInterface;

/**
 * @implements QueryInterface<AccountDealsResponseDTO>
 */
class AccountDealsQuery implements QueryInterface
{
    public function __construct(
        public int $accountId,
        public int $userId
    ) {
    }
}
