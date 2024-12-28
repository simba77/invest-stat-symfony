<?php

declare(strict_types=1);

namespace App\Application\Response\Compiler\Deposits;

use App\Application\Response\DTO\Deposits\DepositAccountEditFormDTO;
use App\Domain\Deposits\DepositAccount;
use App\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<DepositAccount, DepositAccountEditFormDTO>
 */
class DepositAccountFormDataCompiler implements CompilerInterface
{
    /**
     * @param DepositAccount $entry
     * @return DepositAccountEditFormDTO
     */
    public function compile(mixed $entry): DepositAccountEditFormDTO
    {
        return new DepositAccountEditFormDTO(
            id:   $entry->getId(),
            name: $entry->getName()
        );
    }
}
