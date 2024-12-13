<?php

declare(strict_types=1);

namespace App\Services;

class FuturesMultipliers
{
    public function getMultiplierForTicker(string $ticker): ?string
    {
        $multipliers = [
            'SIZ4' => '0.001',
            'SIH5' => '0.001',
            'CRH5' => '1',
            'MXZ4' => '1',
            'MXH5' => '1'
        ];

        if(array_key_exists($ticker, $multipliers)) {
            return $multipliers[$ticker];
        }
        return null;
    }
}
