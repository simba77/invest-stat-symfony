<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Journal;

/**
 * An entry of the journal of a manual account as the operations list shows it.
 *
 * @psalm-api
 */
final readonly class ManualOperationItemDTO
{
    /**
     * @param string $type a ManualOperationType value
     * @param string|null $ticker of the security traded, or of the lot a close or a block is about
     * @param string|null $instrumentType share, bond or future
     * @param string|null $price as the security is quoted: money for a share, percent of the nominal for a bond
     * @param string|null $amount of a cash adjustment or a block of cash
     * @param string|null $currency of the security, or of the cash
     * @param string|null $lotOpenedAt when the lot a close or a block is about was opened
     * @param bool $canCancel a purchase is deleted through its deal instead
     * @param bool $canEdit only the price and the date of a sale can be corrected
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $executedAt,
        public ?string $ticker,
        public ?string $name,
        public ?string $instrumentType,
        public ?int $quantity,
        public ?string $price,
        public ?string $commission,
        public ?string $amount,
        public ?string $currency,
        public ?string $lotOpenedAt,
        public ?string $lotPrice,
        public bool $canCancel,
        public bool $canEdit,
    ) {
    }
}
