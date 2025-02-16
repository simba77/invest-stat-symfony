<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Operations\Deals\PortfolioCompilerData;
use App\Investments\Application\Response\DTO\Compiler\AccountsListCompiler;
use App\Investments\Application\Response\DTO\Operations\FullPortfolioDTO;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Domain\Operations\Deals\GroupByTicker;
use App\Investments\Domain\Operations\Deals\SummaryForGroup;
use App\Shared\Infrastructure\Compiler\CompilerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template-implements CompilerInterface<PortfolioCompilerData, FullPortfolioDTO>
 */
class PortfolioCompiler implements CompilerInterface
{
    public function __construct(
        private readonly AccountsListCompiler $accountsListCompiler,
        private readonly CurrencyService $currencyService,
        private readonly PropertyAccessorInterface $propertyAccess
    ) {
    }

    /**
     * @param PortfolioCompilerData $entry
     * @return FullPortfolioDTO
     */
    public function compile(mixed $entry): FullPortfolioDTO
    {
        $accountsValue = $this->getAccountsValue($entry->accounts);
        $summary = new SummaryForGroup();
        $statuses = new DealListStatuses();
        $instrumentTypes = new DealListInstrumentTypes();
        $currencies = new DealListCurrencies();
        $result = [];

        foreach ($entry->deals as $deal) {
            $status = $statuses->addAndGet($deal['deal']->getStatus());
            $instrumentType = $instrumentTypes->addAndGet($deal);
            $currency = $currencies->add($deal);
            $ticker = $deal['deal']->getTicker();

            $dealData = new DealData($deal, $deal['deal']->getAccount(), $this->currencyService);

            /** @var ?GroupByTicker $group */
            $group = $this->propertyAccess->getValue($result, '[' . $status['code'] . '][' . $instrumentType['code'] . '][' . $currency['code'] . '][' . $ticker . ']');
            if ($group) {
                $group->addDeal($dealData);
            } else {
                $group = new GroupByTicker($accountsValue);
                $group->addDeal($dealData);
                $result[$status['code']][$instrumentType['code']][$currency['code']][$ticker] = $group;
            }

            $summary->addDeal($status['code'], $instrumentType['code'], $currency['code'], $dealData);
        }

        return new FullPortfolioDTO(
            dealsList:       $this->resortTickers($result),
            statuses:        $statuses->getStatuses(),
            instrumentTypes: $instrumentTypes->getInstrumentTypes(),
            currencies:      $currencies->getCurrencies(),
            summary:         $summary->getSummary(),
        );
    }

    /**
     * @param array<int, array{account: Account, deposits_sum: string | null}> $accounts
     */
    private function getAccountsValue(array $accounts): string
    {
        $accountsValue = '0';
        $accountsList = $this->accountsListCompiler->compile($accounts);
        foreach ($accountsList as $account) {
            $accountsValue = bcadd($accountsValue, $account->currentValue, 2);
        }
        return $accountsValue;
    }

    /**
     * @param array<string, array<string, array<string, array<string, GroupByTicker>>>> $result
     * @return array<string, array<string, array<string, array<string, GroupByTicker>>>>
     */
    private function resortTickers(array $result): array
    {
        foreach ($result as $statusCode => $groupByStatus) {
            foreach ($groupByStatus as $instrumentTypeCode => $groupByInstrumentType) {
                foreach ($groupByInstrumentType as $currencyCode => $groupByCurrency) {
                    usort($result[$statusCode][$instrumentTypeCode][$currencyCode], function (GroupByTicker $a, GroupByTicker $b) {
                        if ($a->getGroupData()->percent > $b->getGroupData()->percent) {
                            return -1;
                        } elseif ($a->getGroupData()->percent < $b->getGroupData()->percent) {
                            return 1;
                        }
                        return 0;
                    });
                }
            }
        }

        return $result;
    }
}
