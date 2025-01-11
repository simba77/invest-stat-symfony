<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\Compiler;

use App\Deposits\Application\Response\DTO\DepositAccountEditFormDTO;
use App\Deposits\Domain\DepositAccount;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

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
