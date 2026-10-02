<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Investments\Domain\BrokerSync\Client\ExternalInstrument;
use App\Investments\Domain\BrokerSync\InstrumentKind;
use App\Investments\Domain\BrokerSync\Ledger\InstrumentRef;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\BondRepositoryInterface;
use App\Investments\Domain\Instruments\Securities\ShareTypeEnum;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Finds the local share or bond of a broker instrument, creating it when the catalogue lacks it.
 * The broker uid is stored on the instrument, so that its price gets updated.
 */
final readonly class InstrumentResolver
{
    public function __construct(
        private ShareRepositoryInterface $shareRepository,
        private BondRepositoryInterface $bondRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param \Closure(string): ?ExternalInstrument $lookup asks the broker about an unknown instrument
     */
    public function resolve(InstrumentRef $instrument, \Closure $lookup): Share|Bond|null
    {
        return $instrument->kind === InstrumentKind::Bond
            ? $this->bond($instrument, $lookup)
            : $this->share($instrument, $lookup);
    }

    /**
     * @param \Closure(string): ?ExternalInstrument $lookup
     */
    private function share(InstrumentRef $instrument, \Closure $lookup): ?Share
    {
        $share = $this->shareRepository->findByTUid($instrument->uid)
            ?? $this->shareRepository->findByTickerAndStockMarket($instrument->ticker, $instrument->stockMarket);
        if ($share !== null) {
            if ($share->getTUid() === null) {
                $share->setTUid($instrument->uid);
            }

            return $share;
        }

        $details = $lookup($instrument->uid);
        if ($details === null) {
            return null;
        }

        $share = new Share(
            ticker:      $details->ticker,
            name:        $details->name,
            stockMarket: $instrument->stockMarket,
            currency:    $details->currency,
            price:       '0',
            type:        $details->kind === InstrumentKind::Etf ? ShareTypeEnum::ETF->value : ShareTypeEnum::Stock->value,
            shortName:   $details->name,
            lotSize:     (string) $details->lotSize,
            isin:        $details->isin ?? '',
        );
        $share->setTUid($details->uid);
        $share->setClassCode($details->classCode);
        $this->entityManager->persist($share);

        return $share;
    }

    /**
     * @param \Closure(string): ?ExternalInstrument $lookup
     */
    private function bond(InstrumentRef $instrument, \Closure $lookup): ?Bond
    {
        $bond = $this->bondRepository->findByTUid($instrument->uid)
            ?? $this->bondRepository->findByTickerAndStockMarket($instrument->ticker, $instrument->stockMarket);
        if ($bond !== null) {
            if ($bond->getTUid() === null) {
                $bond->setTUid($instrument->uid);
            }

            return $bond;
        }

        $details = $lookup($instrument->uid);
        if ($details === null) {
            return null;
        }

        $bond = new Bond(
            ticker:      $details->ticker,
            name:        $details->name,
            stockMarket: $instrument->stockMarket,
            currency:    $details->currency,
            price:       '0',
            prevPrice:   '0',
            shortName:   $details->name,
            lotSize:     $details->nominal ?? '1000',
        );
        $bond->setTUid($details->uid);
        $this->entityManager->persist($bond);

        return $bond;
    }
}
