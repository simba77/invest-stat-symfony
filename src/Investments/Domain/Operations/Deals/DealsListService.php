<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Application\Response\DTO\Compiler\AccountsListCompiler;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Infrastructure\Persistence\Repository\DealRepository;
use App\Shared\Domain\User;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DealsListService
{
    public function __construct(
        private readonly DealRepository $dealRepository,
        private readonly PropertyAccessorInterface $propertyAccess,
        private readonly AccountBalanceCalculator $accountBalanceCalculator,
        private readonly CurrencyService $currencyService,
        protected readonly AccountRepositoryInterface $accountRepository,
        protected readonly AccountsListCompiler $accountsListCompiler,
    ) {
    }

    /** @return array<string, mixed> */
    public function getListWithGroups(User $user, Account $account): array
    {
        $result = [];
        $statuses = [];
        $instrumentTypes = [];
        $currencies = [];
        $deals = $this->dealRepository->findForUserAndAccount($user, $account);
        $summary = new SummaryForGroup();
        $accountValue = $this->accountBalanceCalculator->getTotalBalance($account);

        foreach ($deals as $deal) {
            // Statuses
            $status = $this->getStatus($deal);
            $statuses[$status['code']] = $status;

            // Type
            $instrumentType = $this->getInstrumentType($deal);
            $instrumentTypes[$instrumentType['code']] = $instrumentType;

            // Currency
            $currency = $this->getCurrency($deal);
            $currencies[$currency['code']] = $currency;

            $ticker = $deal['deal']->getTicker();

            $dealData = new DealData($deal, $account, $this->currencyService);

            /** @var ?GroupByTicker $group */
            $group = $this->propertyAccess->getValue($result, '[' . $status['code'] . '][' . $instrumentType['code'] . '][' . $currency['code'] . '][' . $ticker . ']');
            if ($group) {
                $group->addDeal($dealData);
            } else {
                $group = new GroupByTicker($accountValue);
                $group->addDeal($dealData);
                $result[$status['code']][$instrumentType['code']][$currency['code']][$ticker] = $group;
            }

            $summary->addDeal($status['code'], $instrumentType['code'], $currency['code'], $dealData);
        }

        return [
            'dealsList'       => $this->resortTickers($result),
            'statuses'        => $statuses,
            'instrumentTypes' => $instrumentTypes,
            'currencies'      => $currencies,
            'summary'         => $summary->getSummary(),
        ];
    }

    /**
     * @param array<string, mixed> $deal
     * @return array{code: string, name: string}
     */
    private function getInstrumentType(array $deal): array
    {
        $type = match (true) {
            $deal['shareName'] !== null => 'shares',
            $deal['bondName'] !== null => 'bonds',
            $deal['futureName'] !== null => 'futures',
            default => 'other',
        };

        $types = [
            'shares'  => [
                'code' => 'shares',
                'name' => 'Shares',
            ],
            'bonds'   => [
                'code' => 'bonds',
                'name' => 'Bonds',
            ],
            'futures' => [
                'code' => 'futures',
                'name' => 'Futures',
            ],
            'other'   => [
                'code' => 'other',
                'name' => 'Other',
            ],
        ];

        return $types[$type];
    }

    /**
     * @param array<string, mixed> $deal
     * @return array{code: string, name: string}
     */
    private function getCurrency(array $deal): array
    {
        $currency = $deal['shareCurrency'] ?? $deal['bondCurrency'] ?? $deal['futureCurrency'] ?? 'RUB';
        $currencies = [
            'USD' => [
                'code' => 'USD',
                'name' => 'US Dollar',
            ],
            'RUB' => [
                'code' => 'RUB',
                'name' => 'Russian Rouble',
            ],
        ];

        return $currencies[$currency];
    }

    /**
     * @param array<string, mixed> $deal
     * @return array{code: string, name: string}
     */
    private function getStatus(array $deal): array
    {
        $status = $deal['deal']->getStatus();

        $statuses = [
            DealStatus::Active->name  => [
                'code' => 'active',
                'name' => 'Active',
            ],
            DealStatus::Closed->name  => [
                'code' => 'closed',
                'name' => 'Closed',
            ],
            DealStatus::Blocked->name => [
                'code' => 'blocked',
                'name' => 'Blocked',
            ],
        ];

        return $statuses[$status->name];
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
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
