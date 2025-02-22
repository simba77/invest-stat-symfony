<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\Operations\Deals\DealStatusDTO;
use App\Investments\Domain\Operations\Deals\DealStatus;

class DealListStatuses
{
    /** @var array<string, DealStatusDTO> */
    private array $statuses = [];

    public function addAndGet(DealStatus $status): DealStatusDTO
    {
        $statusData = $status->codeAndName();
        $this->statuses[$statusData['code']] = new DealStatusDTO($statusData['code'], $statusData['name']);
        return $this->statuses[$statusData['code']];
    }

    /** @return  array<string, DealStatusDTO> */
    public function getStatuses(): array
    {
        return $this->statuses;
    }
}
