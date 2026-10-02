<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Client;

use App\Investments\Domain\BrokerSync\BrokerOperationState;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\InstrumentKind;

/**
 * An operation as the broker reports it, already translated to our types.
 * Money values are signed from the account's point of view, except the absolute commission.
 */
final readonly class ExternalOperation
{
    /**
     * @param numeric-string|null $price price of one security in the operation currency
     * @param numeric-string $payment
     * @param numeric-string|null $commission
     * @param numeric-string|null $accruedInterest accrued coupon interest paid or received with a bond trade
     * @param array<string, mixed> $payload the operation as received, for later inspection
     */
    public function __construct(
        public string $id,
        public ?string $parentId,
        public BrokerOperationType $type,
        public string $rawType,
        public BrokerOperationState $state,
        public \DateTimeImmutable $executedAt,
        public ?string $instrumentUid,
        public ?InstrumentKind $instrumentKind,
        public ?string $ticker,
        public ?string $classCode,
        public ?string $name,
        public int $quantity,
        public ?string $price,
        public string $payment,
        public string $currency,
        public ?string $commission,
        public ?string $accruedInterest,
        public ?string $description,
        public array $payload,
    ) {
    }
}
