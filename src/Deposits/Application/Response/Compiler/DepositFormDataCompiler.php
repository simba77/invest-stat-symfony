<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\Compiler;

use App\Deposits\Application\Response\DTO\DepositEditFormDTO;
use App\Deposits\Domain\Deposit;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<Deposit, DepositEditFormDTO>
 */
class DepositFormDataCompiler implements CompilerInterface
{
    /**
     * @param Deposit $entry
     * @return DepositEditFormDTO
     */
    #[\Override]
    public function compile(mixed $entry): DepositEditFormDTO
    {
        return new DepositEditFormDTO(
            $entry->getId(),
            $entry->getSum(),
            $entry->getType(),
            $entry->getDepositAccount()->getId(),
            $entry->getDate()->format('Y-m-d')
        );
    }
}
