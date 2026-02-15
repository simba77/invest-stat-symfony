<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Application\Response\DTO\Instruments\ShowSharePortfolioDTO;
use App\Investments\Application\Response\DTO\Instruments\ShowShareResponseDTO;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\Exceptions\InstrumentNotFoundException;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Investments\Domain\Instruments\PriceTrendEnum;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;
use App\Investments\Domain\Operations\Deal;
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
    ) {
    }

    public function execute(int $id, int $userId): ShowShareResponseDTO
    {
        $share = $this->shareRepository->findById($id);
        if (! $share) {
            throw new InstrumentNotFoundException(sprintf('Share with id %s not found', $id));
        }

        $deals = $this->dealRepository->findByUserAndShare($userId, $id, DealStatus::Active);

        $dealsGroup = new GroupByTicker('7000000'); // TODO: Change sum

        foreach ($deals as $deal) {
            $dealsGroup->addDeal(new DealData($deal, $this->currencyService, $this->futureMultiplierRepository));
        }
        $allDealsData = $dealsGroup->getGroupData();

        $portfolioDTO = new ShowSharePortfolioDTO(
            quantity:          $allDealsData->quantity,
            fullPrice:         $allDealsData->fullCurrentPrice,
            fullProfit:        $allDealsData->profit,
            fullProfitPercent: $allDealsData->profitPercent,
            fullProfitTrend:   PriceTrendEnum::fromPrices($allDealsData->fullCurrentPrice, $allDealsData->fullBuyPrice),
            averageBuyPrice:   $allDealsData->buyPrice,
            portfolioPercent:  $allDealsData->percent,
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
