<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\BrokerSync;

use App\Investments\Application\Response\DTO\BrokerSync\BrokerExpenseDTO;
use App\Investments\Application\Response\DTO\BrokerSync\BrokerExpensesDTO;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerOperationRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\Ledger\Ledger;

/**
 * Fees and taxes the broker charged the account outside of trades, by year.
 */
final readonly class GetBrokerExpensesUseCase
{
    public function __construct(
        private BrokerOperationRepositoryInterface $operationRepository,
    ) {
    }

    public function execute(BrokerAccountLink $link): BrokerExpensesDTO
    {
        $types = array_values(array_filter(
            BrokerOperationType::cases(),
            static fn (BrokerOperationType $type) => $type->isAccountExpense(),
        ));

        /** @var array<int, array<string, numeric-string>> $sums by year and type */
        $sums = [];
        $total = '0';
        foreach ($this->operationRepository->findExecutedByTypes($link, $types) as $operation) {
            $year = (int) Ledger::localDate($operation->getExecutedAt())->format('Y');
            $type = $operation->getType()->value;
            $amount = bcmul($operation->getPayment(), '-1', 9);
            $sums[$year][$type] = bcadd($sums[$year][$type] ?? '0', $amount, 9);
            $total = bcadd($total, $amount, 9);
        }

        krsort($sums);
        $items = [];
        foreach ($sums as $year => $byType) {
            foreach ($types as $type) {
                if (isset($byType[$type->value])) {
                    $items[] = new BrokerExpenseDTO($year, $type->value, $type->title(), bcadd($byType[$type->value], '0', 2));
                }
            }
        }

        return new BrokerExpensesDTO($items, bcadd($total, '0', 2));
    }
}
