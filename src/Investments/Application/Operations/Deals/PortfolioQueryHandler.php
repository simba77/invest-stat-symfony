<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Application\Response\Compiler\DealListCurrencies;
use App\Investments\Application\Response\Compiler\DealListInstrumentTypes;
use App\Investments\Application\Response\Compiler\DealListStatuses;
use App\Investments\Application\Response\DTO\Operations\FullPortfolioDTO;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Domain\Operations\Deals\GroupByTicker;
use App\Investments\Domain\Operations\Deals\SummaryForGroup;
use App\Shared\Domain\User;
use App\Shared\Domain\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

#[AsMessageHandler]
class PortfolioQueryHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly DealRepositoryInterface $dealRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly CurrencyService $currencyService,
        private readonly PropertyAccessorInterface $propertyAccess,
        private readonly AccountBalanceCalculator $accountBalanceCalculator,
        private readonly FutureMultiplierRepositoryInterface $futureMultiplierRepository
    ) {
    }

    public function __invoke(PortfolioQuery $query): FullPortfolioDTO
    {
        $user = $this->userRepository->findById($query->userId);
        $deals = $this->dealRepository->findByUserId($user->getId());
        $accountBalance = $this->getAccountsBalance($user);

        $summary = new SummaryForGroup();
        $statuses = new DealListStatuses();
        $instrumentTypes = new DealListInstrumentTypes();
        $currencies = new DealListCurrencies();
        $result = [];

        foreach ($deals as $deal) {
            $status = $statuses->addAndGet($deal->getStatus());
            $instrumentType = $instrumentTypes->addAndGet($deal);
            $currency = $currencies->add($deal);
            $ticker = $deal->getTicker();

            $dealData = new DealData($deal, $this->currencyService, $this->futureMultiplierRepository);

            /** @var ?GroupByTicker $group */
            $group = $this->propertyAccess->getValue($result, '[' . $status->code . '][' . $instrumentType->code . '][' . $currency->code . '][' . $ticker . ']');
            if ($group) {
                $group->addDeal($dealData);
            } else {
                $group = new GroupByTicker($accountBalance);
                $group->addDeal($dealData);
                $result[$status->code][$instrumentType->code][$currency->code][$ticker] = $group;
            }

            $summary->addDeal($status->code, $instrumentType->code, $currency->code, $dealData);
        }

        return new FullPortfolioDTO(
            dealsList:       $this->resortTickers($result),
            statuses:        $statuses->getStatuses(),
            instrumentTypes: $instrumentTypes->getInstrumentTypes(),
            currencies:      $currencies->getCurrencies(),
            summary:         $summary->getSummary(),
        );
    }

    private function getAccountsBalance(User $user): string
    {
        $balance = '0';
        $accounts = $this->accountRepository->findByUserWithDeposits($user->getId());
        foreach ($accounts as $account) {
            $balance = bcadd($balance, $this->accountBalanceCalculator->getTotalBalance($account['account']));
        }
        return $balance;
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
