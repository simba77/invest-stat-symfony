<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\Accounts\AccountItemResponseDTO;
use App\Investments\Domain\Accounts\Account;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<array<int, array{account: Account, deposits_sum: string | null}>, array<AccountItemResponseDTO>>
 */
class AccountsListCompiler implements CompilerInterface
{
    public function __construct(
        protected readonly AccountItemCompiler $accountItemCompiler,
    ) {
    }

    /**
     * @param array<int, array{account: Account, deposits_sum: string | null}> $entry
     * @return array<AccountItemResponseDTO>
     */
    public function compile(mixed $entry): array
    {
        $result = [];
        foreach ($entry as $item) {
            $result[] = $this->accountItemCompiler->compile($item);
        }

        return $result;
    }
}
