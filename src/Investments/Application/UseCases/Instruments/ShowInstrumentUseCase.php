<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Application\Response\Compiler\AccountsListCompiler;
use App\Investments\Application\Response\Compiler\ClosedDealsListCompiler;
use App\Investments\Application\Response\DTO\Instruments\ShowBondDetailsDTO;
use App\Investments\Application\Response\DTO\Instruments\ShowFutureDetailsDTO;
use App\Investments\Application\Response\DTO\Instruments\ShowInstrumentDetailsDTOInterface;
use App\Investments\Application\Response\DTO\Instruments\ShowInstrumentResponseDTO;
use App\Investments\Application\Response\DTO\Instruments\ShowShareDetailsDTO;
use App\Investments\Application\Response\DTO\Instruments\ShowShareDividendDTO;
use App\Investments\Application\Response\DTO\Instruments\ShowSharePortfolioDTO;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\BondRepositoryInterface;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\Exceptions\InstrumentNotFoundException;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Investments\Domain\Instruments\FutureRepositoryInterface;
use App\Investments\Domain\Instruments\PriceTrendEnum;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\GroupByTicker;
use App\Investments\Domain\Operations\DividendRepositoryInterface;
use App\Shared\Domain\User;

final readonly class ShowInstrumentUseCase
{
    public function __construct(
        private ShareRepositoryInterface $shareRepository,
        private BondRepositoryInterface $bondRepository,
        private FutureRepositoryInterface $futureRepository,
        private DealRepositoryInterface $dealRepository,
        private CurrencyService $currencyService,
        private FutureMultiplierRepositoryInterface $futureMultiplierRepository,
        private ClosedDealsListCompiler $closedDealsListCompiler,
        private DividendRepositoryInterface $dividendRepository,
        private AccountRepositoryInterface $accountRepository,
        private AccountsListCompiler $accountsListCompiler,
    ) {
    }

    public function execute(string $type, int $id, User $user): ShowInstrumentResponseDTO
    {
        $normalizedType = strtolower($type);
        $userId = $user->getId();

        $allAssetsSum = '0';
        $accounts = $this->accountRepository->findByUserWithDeposits($userId);
        $accountsList = $this->accountsListCompiler->compile($accounts);
        foreach ($accountsList as $account) {
            $allAssetsSum = bcadd($account->currentValue, $allAssetsSum, 2);
        }

        [$name, $ticker, $currencyCode, $price, $prevPrice, $lotSize, $isin, $stockMarket, $details] = $this->resolveInstrument(
            $normalizedType,
            $id
        );

        $activeDeals = $this->findOpenDeals($normalizedType, $userId, $id);

        $dealsGroup = new GroupByTicker($allAssetsSum);
        foreach ($activeDeals as $deal) {
            $dealsGroup->addDeal(new DealData($deal, $this->currencyService, $this->futureMultiplierRepository));
        }

        $openPositions = $dealsGroup->getDeals();

        $activeDealsQuantity = 0;
        $activeDealsFullCurrentPrice = '0';
        $activeDealsProfit = '0';
        $activeDealsProfitPercent = '0';
        $activeDealsFullBuyPrice = '0';
        $activeDealsBuyPrice = '0';
        $activeDealsPercent = '0';

        if (! empty($activeDeals)) {
            $activeDealsData = $dealsGroup->getGroupData();
            $activeDealsQuantity = $activeDealsData->quantity;
            $activeDealsFullCurrentPrice = $activeDealsData->fullCurrentPrice;
            $activeDealsProfit = $activeDealsData->profit;
            $activeDealsProfitPercent = $activeDealsData->profitPercent;
            $activeDealsFullBuyPrice = $activeDealsData->fullBuyPrice;
            $activeDealsBuyPrice = $activeDealsData->buyPrice;
            $activeDealsPercent = $activeDealsData->percent;
        }

        $closedDeals = $this->findDeals($normalizedType, $userId, $id, DealStatus::Closed);
        $closedDealsData = $this->closedDealsListCompiler->compile($closedDeals);

        $sumOfDividends = '0';
        $dividendsList = [];
        if ($normalizedType === 'share') {
            $sumOfDividends = $this->dividendRepository->sumByTickerAndUserAndStockMarket($userId, $ticker, $stockMarket);
            $dividends = $this->dividendRepository->findByUserAndTickerAndStockMarket($userId, $ticker, $stockMarket);
            foreach ($dividends as $dividend) {
                $amount = $dividend->getAmount() ?? '0';

                $dividendsList[] = new ShowShareDividendDTO(
                    id:          $dividend->getId() ?? 0,
                    date:        $dividend->getDate()->format('d.m.Y'),
                    accountName: $dividend->getAccount()?->getName() ?? '',
                    amount:      $amount,
                    tax:         $dividend->getTax(),
                );
            }
        }

        $portfolioDTO = new ShowSharePortfolioDTO(
            quantity:                 $activeDealsQuantity,
            fullPrice:                $activeDealsFullCurrentPrice,
            fullProfit:               $activeDealsProfit,
            fullProfitPercent:        $activeDealsProfitPercent,
            fullProfitTrend:          PriceTrendEnum::fromPrices($activeDealsFullCurrentPrice, $activeDealsFullBuyPrice),
            averageBuyPrice:          $activeDealsBuyPrice,
            portfolioPercent:         $activeDealsPercent,
            closedDealsProfit:        $closedDealsData['summary']->profit,
            closedDealsProfitPercent: $closedDealsData['summary']->profitPercent,
            closedDealsProfitTrend:   PriceTrendEnum::fromPrices($closedDealsData['summary']->sellPrice, $closedDealsData['summary']->buyPrice),
            sumOfDividends:           $sumOfDividends,
        );

        if (! empty($closedDealsData['deals'])) {
            $closedPositions = $closedDealsData['deals'][0]->getDeals();
        }

        $difference = bcsub($price, $prevPrice, 4);
        $percent = $this->calculatePriceChangePercent($price, $prevPrice);

        return new ShowInstrumentResponseDTO(
            id:              $id,
            instrumentType:  $normalizedType,
            name:            $name,
            ticker:          $ticker,
            logo:            null,
            marketName:      $this->getMarket($stockMarket),
            stockMarket:     $stockMarket,
            currency:        $this->getCurrencySymbol($currencyCode),
            currencyCode:    $currencyCode,
            price:           $price,
            prevPrice:       $prevPrice,
            difference:      $difference,
            percent:         $percent,
            lotSize:         $lotSize,
            isin:            $isin,
            priceTrend:      PriceTrendEnum::fromPrices($price, $prevPrice),
            portfolio:       $portfolioDTO,
            openPositions:   $openPositions,
            closedPositions: $closedPositions ?? [],
            dividends:       $dividendsList,
            details:         $details,
        );
    }

    /**
     * @return array{string, string, string, string, string, string, ?string, string, ShowInstrumentDetailsDTOInterface}
     */
    private function resolveInstrument(string $type, int $id): array
    {
        if ($type === 'share') {
            $share = $this->shareRepository->findById($id);
            if (! $share instanceof Share) {
                throw new InstrumentNotFoundException(sprintf('Share with id %s not found', $id));
            }

            return [
                $share->getName(),
                $share->getTicker(),
                $share->getCurrency() ?? 'RUB',
                $share->getPrice(),
                $share->getPrevPrice() ?? '0',
                $share->getLotSize() ?? '1',
                $share->getIsin(),
                $share->getStockMarket(),
                new ShowShareDetailsDTO(),
            ];
        }

        if ($type === 'bond') {
            $bond = $this->bondRepository->findById($id);
            if (! $bond instanceof Bond) {
                throw new InstrumentNotFoundException(sprintf('Bond with id %s not found', $id));
            }

            return [
                $bond->getName(),
                $bond->getTicker(),
                $bond->getCurrency() ?? 'RUB',
                $bond->getPrice(),
                $bond->getPrevPrice() ?? '0',
                $bond->getLotSize() ?? '1',
                null,
                $bond->getStockMarket(),
                new ShowBondDetailsDTO(
                    couponAccumulated: $bond->getCouponAccumulated(),
                ),
            ];
        }

        if ($type === 'future') {
            $future = $this->futureRepository->findById($id);
            if (! $future instanceof Future) {
                throw new InstrumentNotFoundException(sprintf('Future with id %s not found', $id));
            }

            return [
                $future->getName(),
                $future->getTicker(),
                $future->getCurrency(),
                $future->getPrice(),
                $future->getPrevPrice() ?? '0',
                $future->getLotSize() ?? '1',
                null,
                $future->getStockMarket(),
                new ShowFutureDetailsDTO(
                    stepPrice: $future->getStepPrice(),
                ),
            ];
        }

        throw new InstrumentNotFoundException(sprintf('Instrument type %s is not supported', $type));
    }

    /**
     * @return array<int, \App\Investments\Domain\Operations\Deal>
     */
    private function findDeals(string $type, int $userId, int $id, DealStatus $status): array
    {
        return match ($type) {
            'share' => $this->dealRepository->findByUserAndShare($userId, $id, $status),
            'bond' => $this->dealRepository->findByUserAndBond($userId, $id, $status),
            'future' => $this->dealRepository->findByUserAndFuture($userId, $id, $status),
            default => throw new InstrumentNotFoundException(sprintf('Instrument type %s is not supported', $type)),
        };
    }

    /**
     * @return array<int, \App\Investments\Domain\Operations\Deal>
     */
    private function findOpenDeals(string $type, int $userId, int $id): array
    {
        $activeDeals = $this->findDeals($type, $userId, $id, DealStatus::Active);
        $blockedDeals = $this->findDeals($type, $userId, $id, DealStatus::Blocked);

        return array_merge($activeDeals, $blockedDeals);
    }

    private function getMarket(string $stockMarket): string
    {
        if ($stockMarket === 'MOEX') {
            return 'Мосбиржа';
        }
        if ($stockMarket === 'SPB') {
            return 'СПБ Биржа';
        }
        return $stockMarket;
    }

    private function getCurrencySymbol(string $currencyCode): string
    {
        return match ($currencyCode) {
            'RUB' => '₽',
            'CNY' => '¥',
            default => '$',
        };
    }

    private function calculatePriceChangePercent(string $price, string $prevPrice): string
    {
        if (bccomp($prevPrice, '0', 4) === 0) {
            return '0';
        }

        $difference = bcsub($price, $prevPrice, 4);

        return bcmul(bcdiv($difference, $prevPrice, 8), '100', 4);
    }
}
