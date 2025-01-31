<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Compiler;

use App\Investments\Application\Response\DTO\Accounts\AccountResponseDTO;
use App\Investments\Domain\Accounts\Account;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<array<int, Account>, array<AccountResponseDTO>>
 */
class AccountsSimpleListCompiler implements CompilerInterface
{
    /**
     * @param array<int, Account> $entry
     * @return array<AccountResponseDTO>
     */
    public function compile(mixed $entry): array
    {
        $result = [];
        foreach ($entry as $account) {
            $result[] = new AccountResponseDTO(
                id:   $account->getId(),
                name: $account->getName(),
            );
        }

        return $result;
    }
}
