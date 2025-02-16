<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Domain\Operations\Deals\DealStatus;

class DealListStatuses
{
    /** @var array<string, array{code: string, name: string}> */
    private array $statuses = [];

    /**
     * @return array{code: string, name: string}
     */
    public function addAndGet(DealStatus $status): array
    {
        $this->statuses[$status->codeAndName()['code']] = $status->codeAndName();
        return $status->codeAndName();
    }

    /** @return  array<string, array{code: string, name: string}> */
    public function getStatuses(): array
    {
        return $this->statuses;
    }
}
