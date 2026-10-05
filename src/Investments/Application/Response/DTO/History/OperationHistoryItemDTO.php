<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\History;

/**
 * An operation of any account in the history.
 *
 * @psalm-api
 */
final readonly class OperationHistoryItemDTO
{
    /**
     * @param string $key unique in the history: the source and the id
     * @param string $source journal, broker, deposit, dividend or coupon
     * @param string $category an OperationCategory value
     * @param string $priceUnit money, percent (of a bond nominal) or points (of a future)
     * @param string|null $amount the money the operation brought or took, when the source knows it
     */
    public function __construct(
        public string $key,
        public string $source,
        public int $accountId,
        public string $accountName,
        public string $executedAt,
        public string $type,
        public string $title,
        public string $category,
        public ?string $ticker,
        public ?string $name,
        public ?int $quantity,
        public ?string $price,
        public string $priceUnit,
        public ?string $commission,
        public ?string $amount,
        public ?string $currency,
    ) {
    }
}
