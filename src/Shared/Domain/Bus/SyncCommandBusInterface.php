<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus;

interface SyncCommandBusInterface
{
    public function dispatch(object $command): void;
}
