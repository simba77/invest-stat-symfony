<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Compiler;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Application\Response\DTO\Accounts\AccountItemResponseDTO;
use App\Investments\Domain\Accounts\Account;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<array{account: Account, deposits_sum: string | null}, AccountItemResponseDTO>
 */
class AccountItemCompiler implements CompilerInterface
{
    public function __construct(
        private readonly AccountBalanceCalculator $accountBalanceCalculator,
    ) {
    }

    /**
     * @param array{account: Account, deposits_sum: string | null} $entry
     * @return AccountItemResponseDTO
     */
    public function compile(mixed $entry): AccountItemResponseDTO
    {
        $account = $entry['account'];
        $totalBalance = $this->accountBalanceCalculator->getTotalBalance($account);
        $sumDeposits = $entry['deposits_sum'] ?? '0';

        return new AccountItemResponseDTO(
            id:           $account->getId(),
            name:         $account->getName(),
            balance:      $account->getBalance(),
            usdBalance:   $account->getUsdBalance(),
            deposits:     $sumDeposits,
            currentValue: $totalBalance,
            fullProfit:   bcsub($totalBalance, $sumDeposits, 2),
        );
    }
}
