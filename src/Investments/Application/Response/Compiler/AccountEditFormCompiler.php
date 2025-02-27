<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\Accounts\AccountEditFormResponseDTO;
use App\Investments\Domain\Accounts\Account;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<Account, AccountEditFormResponseDTO>
 */
class AccountEditFormCompiler implements CompilerInterface
{
    /**
     * @param Account $entry
     * @return AccountEditFormResponseDTO
     */
    #[\Override]
    public function compile(mixed $entry): AccountEditFormResponseDTO
    {
        return new AccountEditFormResponseDTO(
            name:              $entry->getName(),
            balance:           $entry->getBalance(),
            usdBalance:        $entry->getUsdBalance(),
            commission:        $entry->getCommission(),
            futuresCommission: $entry->getFuturesCommission(),
            sort:              $entry->getSort(),
        );
    }
}
