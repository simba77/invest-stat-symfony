<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Compiler;

use App\Investments\Application\Response\DTO\Accounts\AccountListItemResponseDTO;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountCalculator;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<array<int, array{account: Account, deposits_sum: string | null}>, array<AccountListItemResponseDTO>>
 */
class AccountsListCompiler implements CompilerInterface
{
    public function __construct(
        private readonly AccountCalculator $accountCalculator,
    ) {
    }

    /**
     * @param array<int, array{account: Account, deposits_sum: string | null}> $entry
     * @return array<AccountListItemResponseDTO>
     */
    public function compile(mixed $entry): array
    {
        $result = [];
        foreach ($entry as $item) {
            $account = $item['account'];

            $currentValue = $this->accountCalculator->getAccountValue($account);
            $sumDeposits = $item['deposits_sum'] ?? '0';

            $result[] = new AccountListItemResponseDTO(
                id:           $account->getId(),
                name:         $account->getName(),
                balance:      $account->getBalance(),
                usdBalance:   $account->getUsdBalance(),
                deposits:     $sumDeposits,
                currentValue: $currentValue,
                fullProfit:   bcsub($currentValue, $sumDeposits, 2),
            );
        }

        return $result;
    }
}
