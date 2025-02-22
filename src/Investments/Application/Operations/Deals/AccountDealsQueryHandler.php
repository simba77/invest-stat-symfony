<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Application\Response\Compiler\DealListCurrencies;
use App\Investments\Application\Response\Compiler\DealListInstrumentTypes;
use App\Investments\Application\Response\Compiler\DealListStatuses;
use App\Investments\Application\Response\DTO\Accounts\AccountDealsResponseDTO;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Domain\Operations\Deals\GroupByTicker;
use App\Investments\Domain\Operations\Deals\SummaryForGroup;
use App\Shared\Domain\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

#[AsMessageHandler]
class AccountDealsQueryHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly DealRepositoryInterface $dealRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly CurrencyService $currencyService,
        private readonly PropertyAccessorInterface $propertyAccess,
        private readonly AccountBalanceCalculator $accountBalanceCalculator,
    ) {
    }

    public function __invoke(AccountDealsQuery $query): AccountDealsResponseDTO
    {
        $account = $this->accountRepository->findById($query->accountId);
        $user = $this->userRepository->findById($query->userId);
        $deals = $this->dealRepository->findForUserAndAccount($user->getId(), $account->getId());
        $accountBalance = $this->accountBalanceCalculator->getTotalBalance($account);

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

            $dealData = new DealData($deal, $this->currencyService);

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

        return new AccountDealsResponseDTO(
            dealsList:       $this->resortTickers($result),
            statuses:        $statuses->getStatuses(),
            instrumentTypes: $instrumentTypes->getInstrumentTypes(),
            currencies:      $currencies->getCurrencies(),
            summary:         $summary->getSummary(),
        );
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
