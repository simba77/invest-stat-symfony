<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

enum BrokerOperationState: string
{
    case Executed = 'executed';
    case InProgress = 'in_progress';
    case Canceled = 'canceled';
}
