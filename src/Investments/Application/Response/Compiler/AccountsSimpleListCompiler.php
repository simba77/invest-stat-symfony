<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\Accounts\SimpleAccountItemResponseDTO;
use App\Investments\Domain\Accounts\Account;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<array<int, Account>, array<SimpleAccountItemResponseDTO>>
 */
class AccountsSimpleListCompiler implements CompilerInterface
{
    /**
     * @param array<int, Account> $entry
     * @return array<SimpleAccountItemResponseDTO>
     */
    #[\Override]
    public function compile(mixed $entry): array
    {
        $result = [];
        foreach ($entry as $account) {
            $result[] = new SimpleAccountItemResponseDTO(
                id:   $account->getId(),
                name: $account->getName(),
            );
        }

        return $result;
    }
}
