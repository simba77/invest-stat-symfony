<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Instruments;

use App\Investments\Application\Response\DTO\Instruments\ShowShareResponseDTO;
use App\Investments\Domain\Instruments\Exceptions\InstrumentNotFoundException;
use App\Investments\Domain\Instruments\PriceTrendEnum;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;

final readonly class ShowShareUseCase
{
    public function __construct(
        public ShareRepositoryInterface $shareRepository,
    ) {
    }

    public function execute(int $id): ShowShareResponseDTO
    {
        $share = $this->shareRepository->findById($id);
        if (! $share) {
            throw new InstrumentNotFoundException(sprintf('Share with id %s not found', $id));
        }

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
