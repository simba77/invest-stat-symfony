<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

enum SyncStatus: string
{
    case Success = 'success';

    /** Synced, but the calculated positions or cash differ from the broker. */
    case Warning = 'warning';

    case Failed = 'failed';
}
