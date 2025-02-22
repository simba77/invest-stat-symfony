<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\Operations\Deals\DealInstrumentTypeDTO;
use App\Investments\Domain\Operations\Deal;

class DealListInstrumentTypes
{
    /** @var array<string, DealInstrumentTypeDTO> */
    private array $instrumentTypes = [];

    public function addAndGet(Deal $deal): DealInstrumentTypeDTO
    {
        $type = match (true) {
            $deal->getShare() !== null => 'shares',
            $deal->getBond() !== null => 'bonds',
            $deal->getFuture() !== null => 'futures',
            default => 'other',
        };

        $types = [
            'shares'  => [
                'code' => 'shares',
                'name' => 'Shares',
            ],
            'bonds'   => [
                'code' => 'bonds',
                'name' => 'Bonds',
            ],
            'futures' => [
                'code' => 'futures',
                'name' => 'Futures',
            ],
            'other'   => [
                'code' => 'other',
                'name' => 'Other',
            ],
        ];

        $instrumentType = new DealInstrumentTypeDTO($types[$type]['code'], $types[$type]['name']);
        $this->instrumentTypes[$instrumentType->code] = $instrumentType;
        return $instrumentType;
    }

    /** @return  array<string, DealInstrumentTypeDTO> */
    public function getInstrumentTypes(): array
    {
        return $this->instrumentTypes;
    }
}
