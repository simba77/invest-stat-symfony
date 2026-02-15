<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Application\Response\Compiler\ClosedDealsListCompiler;
use App\Investments\Application\Response\DTO\Instruments\ShowSharePortfolioDTO;
use App\Investments\Application\Response\DTO\Instruments\ShowShareResponseDTO;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\Exceptions\InstrumentNotFoundException;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Investments\Domain\Instruments\PriceTrendEnum;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\GroupByTicker;

final readonly class ShowShareUseCase
{
    public function __construct(
        private ShareRepositoryInterface $shareRepository,
        private DealRepositoryInterface $dealRepository,
        private CurrencyService $currencyService,
        private FutureMultiplierRepositoryInterface $futureMultiplierRepository,
        private ClosedDealsListCompiler $closedDealsListCompiler,
    ) {
    }

    public function execute(int $id, int $userId): ShowShareResponseDTO
    {
        $share = $this->shareRepository->findById($id);
        if (! $share) {
            throw new InstrumentNotFoundException(sprintf('Share with id %s not found', $id));
        }

        $activeDeals = $this->dealRepository->findByUserAndShare($userId, $id, DealStatus::Active);

        $dealsGroup = new GroupByTicker('7000000'); // TODO: Change sum
        foreach ($activeDeals as $deal) {
            $dealsGroup->addDeal(new DealData($deal, $this->currencyService, $this->futureMultiplierRepository));
        }
        $activeDealsData = $dealsGroup->getGroupData();

        $closedDeals = $this->dealRepository->findByUserAndShare($userId, $id, DealStatus::Closed);
        $closedDealsData = $this->closedDealsListCompiler->compile($closedDeals);

        $portfolioDTO = new ShowSharePortfolioDTO(
            quantity:                 $activeDealsData->quantity,
            fullPrice:                $activeDealsData->fullCurrentPrice,
            fullProfit:               $activeDealsData->profit,
            fullProfitPercent:        $activeDealsData->profitPercent,
            fullProfitTrend:          PriceTrendEnum::fromPrices($activeDealsData->fullCurrentPrice, $activeDealsData->fullBuyPrice),
            averageBuyPrice:          $activeDealsData->buyPrice,
            portfolioPercent:         $activeDealsData->percent,
            closedDealsProfit:        $closedDealsData['summary']->profit,
            closedDealsProfitPercent: $closedDealsData['summary']->profitPercent,
            closedDealsProfitTrend:   PriceTrendEnum::fromPrices($closedDealsData['summary']->sellPrice, $closedDealsData['summary']->buyPrice)
        );

        return new ShowShareResponseDTO(
            id:           $id,
            name:         $share->getName(),
            ticker:       $share->getTicker(),
            logo:         null,
            marketName:   $this->getMarket($share->getStockMarket()),
            currency:     $share->getCurrencyEnum()->symbol(),
            currencyCode: $share->getCurrency(),
            price:        $share->getPrice(),
            prevPrice:    $share->getPrevPrice(),
            difference:   $share->getPriceDifference(),
            percent:      $share->getPriceChangePercent(),
            lotSize:      $share->getLotSize(),
            isin:         $share->getIsin(),
            priceTrend:   $share->getPriceTrend(),
            portfolio:    $portfolioDTO,
        );
    }

    private function getMarket(string $stockMarket): string
    {
        if ($stockMarket === 'MOEX') {
            return 'Мосбиржа';
        } elseif ($stockMarket === 'SPB') {
            return 'СПБ Биржа';
        }
        return $stockMarket;
    }
}
