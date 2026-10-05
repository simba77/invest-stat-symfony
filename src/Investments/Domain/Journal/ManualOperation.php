<?php

declare(strict_types=1);

namespace App\Investments\Domain\Journal;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Instrument;
use App\Investments\Infrastructure\Persistence\Repository\ManualOperationRepository;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * An entry of the journal of a manual account. The deals and the cash of the account are rebuilt
 * from its journal, payouts and deposits, so the journal is the source of truth for trades.
 *
 * Prices are kept as the security is quoted: money for a share, percent of the nominal for a bond,
 * points for a future.
 */
#[ORM\Entity(repositoryClass: ManualOperationRepository::class)]
#[ORM\Table(name: 'manual_operations')]
#[ORM\Index(columns: ['account_id', 'executed_at'], name: 'manual_operation_executed_at')]
class ManualOperation implements CreatedDateProviderInterface, UpdatedDateProviderInterface
{
    use CreatedDateProvider;
    use UpdatedDateProvider;

    /** Lot keys start with it, so that a deal leads back to the operation that opened it. */
    public const string LOT_PREFIX = 'op:';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(length: 32, enumType: ManualOperationType::class)]
    private ManualOperationType $type;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $executedAt;

    /** Load it together with the operation: Doctrine cannot load an Instrument lazily. */
    #[ORM\ManyToOne(targetEntity: Instrument::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Instrument $instrument = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $ticker = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $stockMarket = null;

    #[ORM\Column(nullable: true)]
    private ?int $quantity = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $price = null;

    /**
     * The coupon accrued on a bond, per bond, paid on a purchase and received on a sale.
     *
     * @var numeric-string|null
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $accruedInterest = null;

    /**
     * What the broker charged for the trade; unknown for most trades entered by hand.
     *
     * @var numeric-string|null
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $commission = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $targetPrice = null;

    /** The lot a close, a block or an unblock is about. */
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $lot = null;

    /**
     * The money a cash adjustment adds or takes, or the sum a block of cash blocks or frees.
     *
     * @var numeric-string|null
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $currency = null;

    private function __construct(Account $account, ManualOperationType $type, \DateTimeImmutable $executedAt)
    {
        $this->account = $account;
        $this->type = $type;
        $this->executedAt = $executedAt;
    }

    /**
     * @param numeric-string $price
     * @param numeric-string|null $targetPrice
     * @param numeric-string|null $accruedInterest
     * @param numeric-string|null $commission
     */
    public static function open(
        Account $account,
        ManualOperationType $type,
        \DateTimeImmutable $executedAt,
        ?Instrument $instrument,
        string $ticker,
        string $stockMarket,
        int $quantity,
        string $price,
        ?string $targetPrice = null,
        ?string $accruedInterest = null,
        ?string $commission = null,
    ): self {
        if ($type->direction() === null) {
            throw new \InvalidArgumentException(sprintf('%s does not open a lot', $type->value));
        }
        $operation = new self($account, $type, $executedAt);
        $operation->setSecurity($instrument, $ticker, $stockMarket);
        $operation->quantity = $quantity;
        $operation->price = $price;
        $operation->targetPrice = $targetPrice;
        $operation->accruedInterest = $accruedInterest;
        $operation->commission = $commission;

        return $operation;
    }

    /**
     * @param string|null $lot the lot to close; without it, the oldest lots that are not blocked
     * @param numeric-string $price
     * @param numeric-string|null $accruedInterest
     * @param numeric-string|null $commission
     */
    public static function close(
        Account $account,
        \DateTimeImmutable $executedAt,
        ?Instrument $instrument,
        string $ticker,
        string $stockMarket,
        ?string $lot,
        int $quantity,
        string $price,
        ?string $accruedInterest = null,
        ?string $commission = null,
    ): self {
        $operation = new self($account, ManualOperationType::Close, $executedAt);
        $operation->setSecurity($instrument, $ticker, $stockMarket);
        $operation->lot = $lot;
        $operation->quantity = $quantity;
        $operation->price = $price;
        $operation->accruedInterest = $accruedInterest;
        $operation->commission = $commission;

        return $operation;
    }

    public static function block(Account $account, \DateTimeImmutable $executedAt, string $lot, bool $blocked = true): self
    {
        $operation = new self($account, $blocked ? ManualOperationType::Block : ManualOperationType::Unblock, $executedAt);
        $operation->lot = $lot;

        return $operation;
    }

    /**
     * @param numeric-string $amount
     */
    public static function cashAdjustment(Account $account, \DateTimeImmutable $executedAt, string $currency, string $amount): self
    {
        $operation = new self($account, ManualOperationType::CashAdjustment, $executedAt);
        $operation->currency = $currency;
        $operation->amount = $amount;

        return $operation;
    }

    /**
     * @param numeric-string $amount blocked when positive, freed when negative
     */
    public static function blockCash(Account $account, \DateTimeImmutable $executedAt, string $currency, string $amount): self
    {
        $operation = new self($account, ManualOperationType::BlockCash, $executedAt);
        $operation->currency = $currency;
        $operation->amount = $amount;

        return $operation;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * The key of the lot the operation opens.
     */
    public function getOpenedLot(): string
    {
        return self::LOT_PREFIX . ($this->id ?? throw new \LogicException('The operation is not saved yet'));
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getType(): ManualOperationType
    {
        return $this->type;
    }

    public function getExecutedAt(): \DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function getInstrument(): ?Instrument
    {
        return $this->instrument;
    }

    public function getTicker(): ?string
    {
        return $this->ticker;
    }

    public function getStockMarket(): ?string
    {
        return $this->stockMarket;
    }

    public function getQuantity(): int
    {
        return $this->quantity ?? 0;
    }

    /**
     * @return numeric-string
     */
    public function getPrice(): string
    {
        return $this->price ?? '0';
    }

    /**
     * @return numeric-string|null
     */
    public function getAccruedInterest(): ?string
    {
        return $this->accruedInterest;
    }

    /**
     * @return numeric-string|null
     */
    public function getCommission(): ?string
    {
        return $this->commission;
    }

    /**
     * @return numeric-string|null
     */
    public function getTargetPrice(): ?string
    {
        return $this->targetPrice;
    }

    public function getLot(): ?string
    {
        return $this->lot;
    }

    /**
     * @return numeric-string
     */
    public function getAmount(): string
    {
        return $this->amount ?? '0';
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Corrects the purchase or the short sale the operation opened. A commission known before is
     * charged again by the tariff for the corrected trade; an unknown one stays unknown.
     *
     * @param numeric-string $price
     * @param numeric-string|null $targetPrice
     */
    public function correctOpening(
        ManualOperationType $type,
        ?Instrument $instrument,
        string $ticker,
        string $stockMarket,
        int $quantity,
        string $price,
        ?string $targetPrice,
    ): void {
        if ($this->type->direction() === null || $type->direction() === null) {
            throw new \LogicException('Only an opening operation can be corrected');
        }
        $this->type = $type;
        $this->setSecurity($instrument, $ticker, $stockMarket);
        $this->quantity = $quantity;
        $this->price = $price;
        $this->targetPrice = $targetPrice;
        if ($this->commission !== null) {
            $this->commission = $this->account->tradeCommission($instrument, $price, $quantity);
        }
    }

    private function setSecurity(?Instrument $instrument, string $ticker, string $stockMarket): void
    {
        $this->instrument = $instrument;
        $this->ticker = $ticker;
        $this->stockMarket = $stockMarket;
    }
}
