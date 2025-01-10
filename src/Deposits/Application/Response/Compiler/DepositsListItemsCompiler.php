<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\Compiler;

use App\Deposits\Application\Response\DTO\DepositListItemDTO;
use App\Deposits\Domain\Deposit;
use App\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<iterable<Deposit>, iterable<DepositListItemDTO>>
 */
class DepositsListItemsCompiler implements CompilerInterface
{
    /**
     * @param Deposit[] $entry
     * @return DepositListItemDTO[]
     */
    public function compile(mixed $entry): array
    {
        $result = [];
        foreach ($entry as $deposit) {
            $result[] = new DepositListItemDTO(
                id:          $deposit->getId(),
                date:        $deposit->getDate()->format('d.m.Y'),
                sum:         $deposit->getSum(),
                typeName:    $deposit->getType() === 1 ? 'Deposit' : 'Percent',
                accountName: $deposit->getDepositAccount()->getName()
            );
        }
        return $result;
    }
}
