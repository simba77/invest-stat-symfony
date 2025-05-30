<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Compiler;

/**
 * @template TInput
 * @template TOutput
 */
interface CompilerInterface
{
    /**
     * @param TInput $entry
     * @return TOutput
     */
    public function compile(mixed $entry): mixed;
}
