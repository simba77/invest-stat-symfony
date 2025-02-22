<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\Response\DTO\Operations\FullPortfolioDTO;
use App\Shared\Domain\Bus\QueryInterface;

/**
 * @implements QueryInterface<FullPortfolioDTO>
 */
class PortfolioQuery implements QueryInterface
{
    public function __construct(
        public int $userId
    ) {
    }
}
