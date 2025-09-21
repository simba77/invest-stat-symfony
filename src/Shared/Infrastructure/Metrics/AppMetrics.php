<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

class AppMetrics
{
    public const string START_TIME = 'start_time';
    public const string END_TIME = 'end_time';
    public const string EXECUTION_TIME = 'execution_time';

    private array $metrics = [];

    public function __construct()
    {
        $this->metrics[self::START_TIME] = microtime(true);
    }

    public function getMetrics(): array
    {
        $this->metrics[self::END_TIME] = microtime(true);
        $this->metrics[self::EXECUTION_TIME] = $this->metrics[self::END_TIME] - $this->metrics[self::START_TIME];

        return $this->metrics;
    }

    public function getExecutionTime(): float
    {
        return round($this->getMetrics()[self::EXECUTION_TIME], 4);
    }
}
