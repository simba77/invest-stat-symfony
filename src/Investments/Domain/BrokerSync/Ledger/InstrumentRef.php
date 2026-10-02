<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Ledger;

use App\Investments\Domain\BrokerSync\BrokerOperation;
use App\Investments\Domain\BrokerSync\InstrumentKind;

/**
 * An instrument as the broker identifies it.
 */
final readonly class InstrumentRef
{
    public function __construct(
        public string $uid,
        public string $ticker,
        public string $stockMarket,
        public ?string $classCode,
        public ?InstrumentKind $kind,
        public ?string $name,
    ) {
    }

    public static function fromOperation(BrokerOperation $operation): ?self
    {
        $uid = $operation->getInstrumentUid();
        if ($uid === null) {
            return null;
        }

        return new self(
            uid:         $uid,
            ticker:      $operation->getTicker() ?? $uid,
            stockMarket: self::stockMarketOf($operation->getClassCode()),
            classCode:   $operation->getClassCode(),
            kind:        $operation->getInstrumentKind(),
            name:        $operation->getName(),
        );
    }

    /**
     * The exchange by the board the instrument trades on: SPB boards start with "SPB".
     */
    public static function stockMarketOf(?string $classCode): string
    {
        return $classCode !== null && str_starts_with(strtoupper($classCode), 'SPB') ? 'SPB' : 'MOEX';
    }

    /**
     * Securities that become deals; cash, futures and options are only counted in money.
     */
    public function isTradable(): bool
    {
        return in_array($this->kind, [InstrumentKind::Share, InstrumentKind::Etf, InstrumentKind::Bond], true);
    }
}
