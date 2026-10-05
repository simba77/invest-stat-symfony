<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\History;

/**
 * An operation of any account as the history shows it.
 */
final readonly class OperationHistoryEntry
{
    /**
     * @param string $type a value of the type enum of the source: ManualOperationType, BrokerOperationType,
     *        or the name of the source for the records
     * @param string|null $instrumentKind share, etf, bond, future
     * @param numeric-string|null $price as the source keeps it: the journal quotes a bond in percent of its nominal
     * @param numeric-string|null $amount the money the operation brought or took, when the source knows it
     */
    public function __construct(
        public OperationHistorySource $source,
        public int $id,
        public int $accountId,
        public string $accountName,
        public \DateTimeImmutable $executedAt,
        public string $type,
        public ?string $ticker,
        public ?string $name,
        public ?string $instrumentKind,
        public ?int $quantity,
        public ?string $price,
        public ?string $commission,
        public ?string $amount,
        public ?string $currency,
    ) {
    }
}
