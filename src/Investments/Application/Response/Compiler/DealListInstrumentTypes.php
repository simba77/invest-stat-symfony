<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Domain\Operations\Deal;

class DealListInstrumentTypes
{
    /** @var array<string, array{code: string, name: string}> */
    private array $instrumentTypes = [];

    /**
     * @param array{
     *      deal: Deal,
     *      shareName: ?string,
     *      sharePrice: string,
     *      sharePrevPrice: string,
     *      shareCurrency: string,
     *      shareType: string,
     *      bondName: ?string,
     *      bondPrice: string,
     *      bondPrevPrice: string,
     *      bondCurrency: string,
     *      bondLotSize: string,
     *      futureName: ?string,
     *      futurePrice: string,
     *      futurePrevPrice: string,
     *      futureCurrency: string,
     *      futureStepPrice: string,
     *      futureLotSize: string,
     *  } $deal
     * @return array{code: string, name: string}
     */
    public function addAndGet(array $deal): array
    {
        $type = match (true) {
            $deal['shareName'] !== null => 'shares',
            $deal['bondName'] !== null => 'bonds',
            $deal['futureName'] !== null => 'futures',
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

        $instrumentType = $types[$type];
        $this->instrumentTypes[$instrumentType['code']] = $instrumentType;
        return $instrumentType;
    }

    /** @return  array<string, array{code: string, name: string}> */
    public function getInstrumentTypes(): array
    {
        return $this->instrumentTypes;
    }
}
