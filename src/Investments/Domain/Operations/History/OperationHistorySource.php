<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\History;

/**
 * Where an operation of the history comes from. A manual account tells its trades by its journal
 * and its deposits and payouts by their records; a synced account tells everything by the
 * operations the broker reported.
 */
enum OperationHistorySource: string
{
    case Journal = 'journal';
    case Broker = 'broker';
    case Deposit = 'deposit';
    case Dividend = 'dividend';
    case Coupon = 'coupon';
}
