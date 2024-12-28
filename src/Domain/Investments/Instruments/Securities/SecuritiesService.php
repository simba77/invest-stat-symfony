<?php

declare(strict_types=1);

namespace App\Domain\Investments\Instruments\Securities;

use App\Application\Response\DTO\Investments\Instruments\SecurityDTO;
use App\Domain\Investments\Instruments\Bond;
use App\Domain\Investments\Instruments\Future;
use App\Domain\Investments\Instruments\Share;
use Doctrine\ORM\EntityManagerInterface;

class SecuritiesService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function getSecurityByTicker(string $ticker): ?SecurityDTO
    {
        $share = $this->entityManager->getRepository(Share::class)->findOneBy(['ticker' => $ticker]);
        if ($share) {
            return new SecurityDTO(
                ticker:       $share->getTicker(),
                shortName:    $share->getShortName(),
                stockMarket:  $share->getStockMarket(),
                price:        $share->getPrice() ?? '0',
                lotSize:      $share->getLotSize() ?? '1',
                currency:     $share->getCurrency(),
                securityType: SecurityTypeEnum::Share,
            );
        }

        $bond = $this->entityManager->getRepository(Bond::class)->findOneBy(['ticker' => $ticker]);
        if ($bond) {
            return new SecurityDTO(
                ticker:                $bond->getTicker(),
                shortName:             $bond->getShortName(),
                stockMarket:           $bond->getStockMarket(),
                price:                 $bond->getPrice() ?? '0',
                lotSize:               $bond->getLotSize(),
                currency:              $bond->getCurrency(),
                securityType:          SecurityTypeEnum::Bond,
                bondAccumulatedCoupon: $bond->getCouponAccumulated(),
            );
        }

        $future = $this->entityManager->getRepository(Future::class)->findOneBy(['ticker' => $ticker]);
        if ($future) {
            return new SecurityDTO(
                ticker:       $future->getTicker(),
                shortName:    $future->getShortName(),
                stockMarket:  $future->getStockMarket(),
                price:        $future->getPrice() ?? '0',
                lotSize:      $future->getLotSize(),
                currency:     $future->getCurrency(),
                securityType: SecurityTypeEnum::Future,
            );
        }

        return null;
    }

    public function getSecurityByTickerAndStockMarket(string $ticker, string $stockMarket): ?SecurityDTO
    {
        $share = $this->entityManager->getRepository(Share::class)->findOneBy(['ticker' => $ticker, 'stockMarket' => $stockMarket]);
        if ($share) {
            return new SecurityDTO(
                ticker:       $share->getTicker(),
                shortName:    $share->getShortName(),
                stockMarket:  $share->getStockMarket(),
                price:        $share->getPrice() ?? '0',
                lotSize:      $share->getLotSize() ?? '1',
                currency:     $share->getCurrency(),
                securityType: SecurityTypeEnum::Share,
            );
        }

        $bond = $this->entityManager->getRepository(Bond::class)->findOneBy(['ticker' => $ticker, 'stockMarket' => $stockMarket]);
        if ($bond) {
            return new SecurityDTO(
                ticker:                $bond->getTicker(),
                shortName:             $bond->getShortName(),
                stockMarket:           $bond->getStockMarket(),
                price:                 $bond->getPrice() ?? '0',
                lotSize:               $bond->getLotSize(),
                currency:              $bond->getCurrency(),
                securityType:          SecurityTypeEnum::Bond,
                bondAccumulatedCoupon: $bond->getCouponAccumulated(),
            );
        }

        $future = $this->entityManager->getRepository(Future::class)->findOneBy(['ticker' => $ticker, 'stockMarket' => $stockMarket]);
        if ($future) {
            return new SecurityDTO(
                ticker:       $future->getTicker(),
                shortName:    $future->getShortName(),
                stockMarket:  $future->getStockMarket(),
                price:        $future->getPrice() ?? '0',
                lotSize:      $future->getLotSize(),
                currency:     $future->getCurrency(),
                securityType: SecurityTypeEnum::Future,
            );
        }

        return null;
    }
}