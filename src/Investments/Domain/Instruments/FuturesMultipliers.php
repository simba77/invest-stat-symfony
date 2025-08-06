<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

class FuturesMultipliers
{
    public function getMultiplierForTicker(string $ticker): ?string
    {
        $multipliers = [
            'SIZ4' => '0.001',
            'SIH5' => '0.001',
            'CRH5' => '1',
            'MXZ4' => '1',
            'MXH5' => '1',
            'EUM5' => '0.001',
            'SIM5' => '0.001',
            'SIU5' => '0.001',
            'MXM5' => '1',
            'MXU5' => '1',
            'AKM5' => '0.001',
            'SIZ5' => '0.001',
        ];

        if(array_key_exists($ticker, $multipliers)) {
            return $multipliers[$ticker];
        }
        return null;
    }
}
