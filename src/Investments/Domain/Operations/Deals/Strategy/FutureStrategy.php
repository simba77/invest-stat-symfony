<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;
use RuntimeException;

class FutureStrategy implements DealStrategyInterface
{
    private ?FutureMultiplierRepositoryInterface $futureMultiplierRepository = null;

    public function __construct(
        private readonly Deal $deal
    ) {
    }

    public function setFutureMultiplierRepository(FutureMultiplierRepositoryInterface $futureMultiplierRepository): self
    {
        $this->futureMultiplierRepository = $futureMultiplierRepository;
        return $this;
    }

    private function getFuture(): Future
    {
        return $this->deal->getFuture() ?? throw new RuntimeException('Future is null');
    }

    public function getName(): string
    {
        return $this->getFuture()->getShortName() ?? $this->getFuture()->getName();
    }

    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Future;
    }

    public function getBuyPrice(): string
    {
        return bcmul(bcmul($this->deal->getBuyPrice(), $this->getMultiplier(), 4), $this->getFuture()->getLotSize(), 4);
    }

    public function getSellPrice(): string
    {
        return bcmul(bcmul($this->deal->getSellPrice() ?? '0', $this->getMultiplier(), 4), $this->getFuture()->getLotSize() ?? '1', 4);
    }

    public function getCurrentPrice(): string
    {
        return bcmul(bcmul($this->deal->getFuture()->getPrice(), $this->getMultiplier(), 4), $this->deal->getFuture()->getLotSize(), 4);
    }

    public function getPrevPrice(): string
    {
        return bcmul(bcmul($this->deal->getFuture()->getPrevPrice(), $this->getMultiplier(), 4), $this->deal->getFuture()->getLotSize(), 4);
    }

    public function getCommission(string $price, string $quantity): string
    {
        return bcmul('5', $quantity, 4);
    }

    public function getCurrency(): string
    {
        return $this->deal->getFuture()->getCurrency();
    }

    /**
     * @return numeric-string
     */
    private function getMultiplier(): string
    {
        if ($this->futureMultiplierRepository === null) {
            return $this->deal->getFuture()?->getStepPrice() ?? '1';
        }

        $multiplierFromDb = $this->futureMultiplierRepository->findByTicker($this->deal->getTicker());
        if ($multiplierFromDb !== null) {
            return $multiplierFromDb->getValue();
        }

        return $this->deal->getFuture()?->getStepPrice() ?? '1';
    }

    public function getInstrumentId(): ?int
    {
        return $this->deal->getFuture()->getId();
    }
}
