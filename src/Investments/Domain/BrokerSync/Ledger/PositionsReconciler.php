<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Ledger;

use App\Investments\Domain\BrokerSync\Client\ExternalPositions;
use App\Investments\Domain\BrokerSync\InstrumentKind;

/**
 * Compares the positions and cash rebuilt from operations with what the broker reports.
 * A difference means an operation the replay does not understand, such as a conversion.
 */
final class PositionsReconciler
{
    private const string CASH_CURRENCY = 'RUB';
    private const string CASH_TOLERANCE = '0.01';

    /**
     * @return list<array{name: string, broker: string, calculated: string}>
     */
    public function compare(Ledger $ledger, ExternalPositions $broker): array
    {
        $tickers = [];
        $brokerQuantities = [];
        foreach ($broker->securities as $position) {
            if (! in_array($position->kind, [InstrumentKind::Share, InstrumentKind::Etf, InstrumentKind::Bond], true)) {
                continue;
            }
            $brokerQuantities[$position->instrumentUid] = ($brokerQuantities[$position->instrumentUid] ?? 0) + $position->quantity;
            $tickers[$position->instrumentUid] = $position->ticker;
        }

        $discrepancies = [];
        $uids = array_unique([...array_keys($brokerQuantities), ...array_keys($ledger->positions)]);
        foreach ($uids as $uid) {
            $uid = (string) $uid;
            $brokerQuantity = $brokerQuantities[$uid] ?? 0;
            $calculated = $ledger->positions[$uid] ?? 0;
            if ($brokerQuantity !== $calculated) {
                $discrepancies[] = [
                    'name'       => $tickers[$uid] ?? $ledger->instruments[$uid]->ticker ?? $uid,
                    'broker'     => (string) $brokerQuantity,
                    'calculated' => (string) $calculated,
                ];
            }
        }

        $brokerCash = $broker->money[self::CASH_CURRENCY] ?? '0';
        $calculatedCash = $ledger->cash[self::CASH_CURRENCY] ?? '0';
        if (bccomp(self::absolute(bcsub($brokerCash, $calculatedCash, 9)), self::CASH_TOLERANCE, 9) > 0) {
            $discrepancies[] = [
                'name'       => self::CASH_CURRENCY,
                'broker'     => bcadd($brokerCash, '0', 2),
                'calculated' => bcadd($calculatedCash, '0', 2),
            ];
        }

        return $discrepancies;
    }

    /**
     * @param numeric-string $value
     * @return numeric-string
     */
    private static function absolute(string $value): string
    {
        return bccomp($value, '0', 9) < 0 ? bcmul($value, '-1', 9) : $value;
    }
}
