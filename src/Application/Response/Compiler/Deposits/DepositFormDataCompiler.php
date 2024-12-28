<?php

declare(strict_types=1);

namespace App\Application\Response\Compiler\Deposits;

use App\Application\Response\DTO\Deposits\DepositEditFormDTO;
use App\Domain\Deposits\Deposit;
use App\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<Deposit, DepositEditFormDTO>
 */
class DepositFormDataCompiler implements CompilerInterface
{
    /**
     * @param Deposit $entry
     * @return DepositEditFormDTO
     */
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
