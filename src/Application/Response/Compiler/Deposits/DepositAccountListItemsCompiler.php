<?php

declare(strict_types=1);

namespace App\Application\Response\Compiler\Deposits;

use App\Application\Response\DTO\Deposits\DepositAccountListItemDTO;
use App\Domain\Deposits\DepositAccount;
use App\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<iterable<DepositAccount>, iterable<DepositAccountListItemDTO>>
 */
class DepositAccountListItemsCompiler implements CompilerInterface
{
    /**
     * @param DepositAccount[] $entry
     * @return DepositAccountListItemDTO[]
     */
    public function compile(mixed $entry): array
    {
        $result = [];
        foreach ($entry as $account) {
            $result[] = new DepositAccountListItemDTO(
                id:   $account->getId(),
                name: $account->getName()
            );
        }
        return $result;
    }
}
