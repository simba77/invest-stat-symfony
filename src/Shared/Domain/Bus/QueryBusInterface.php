<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus;

use Throwable;

interface QueryBusInterface
{
    /**
     * @template T
     * @param QueryInterface<T> $query
     * @return T
     * @throws Throwable
     */
    public function ask(QueryInterface $query): mixed;
}
