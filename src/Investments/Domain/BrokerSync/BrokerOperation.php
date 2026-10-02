<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

use App\Investments\Domain\BrokerSync\Client\ExternalOperation;
use App\Investments\Infrastructure\Persistence\Repository\BrokerOperationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal entry: a broker operation exactly as last received. Deals, payouts and deposits of a
 * synced account are rebuilt from these entries, so the journal is the source of truth.
 */
#[ORM\Entity(repositoryClass: BrokerOperationRepository::class)]
#[ORM\Table(name: 'broker_operations')]
#[ORM\UniqueConstraint(name: 'broker_operation_external_id', columns: ['link_id', 'external_id'])]
#[ORM\Index(columns: ['link_id', 'executed_at'], name: 'broker_operation_executed_at')]
class BrokerOperation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BrokerAccountLink::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private BrokerAccountLink $link;

    #[ORM\Column(length: 64)]
    private string $externalId;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $parentExternalId;

    #[ORM\Column(length: 32, enumType: BrokerOperationType::class)]
    private BrokerOperationType $type;

    #[ORM\Column(length: 64)]
    private string $rawType;

    #[ORM\Column(length: 16, enumType: BrokerOperationState::class)]
    private BrokerOperationState $state;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $executedAt;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $instrumentUid;

    #[ORM\Column(length: 16, nullable: true, enumType: InstrumentKind::class)]
    private ?InstrumentKind $instrumentKind;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $ticker;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $classCode;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name;

    #[ORM\Column(type: Types::BIGINT)]
    private string $quantity;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 24, scale: 9, nullable: true)]
    private ?string $price;

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 24, scale: 9)]
    private string $payment;

    #[ORM\Column(length: 8)]
    private string $currency;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 24, scale: 9, nullable: true)]
    private ?string $commission;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 24, scale: 9, nullable: true)]
    private ?string $accruedInterest;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $payload;

    public function __construct(BrokerAccountLink $link, ExternalOperation $operation)
    {
        $this->link = $link;
        $this->externalId = $operation->id;
        $this->update($operation);
    }

    /**
     * Brokers revise operations: a pending one gets executed or canceled, a fee gets linked.
     */
    public function update(ExternalOperation $operation): void
    {
        $this->parentExternalId = $operation->parentId;
        $this->type = $operation->type;
        $this->rawType = $operation->rawType;
        $this->state = $operation->state;
        $this->executedAt = $operation->executedAt;
        $this->instrumentUid = $operation->instrumentUid;
        $this->instrumentKind = $operation->instrumentKind;
        $this->ticker = $operation->ticker;
        $this->classCode = $operation->classCode;
        $this->name = $operation->name;
        $this->quantity = (string) $operation->quantity;
        $this->price = $operation->price;
        $this->payment = $operation->payment;
        $this->currency = $operation->currency;
        $this->commission = $operation->commission;
        $this->accruedInterest = $operation->accruedInterest;
        $this->description = $operation->description;
        $this->payload = $operation->payload;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLink(): BrokerAccountLink
    {
        return $this->link;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getParentExternalId(): ?string
    {
        return $this->parentExternalId;
    }

    public function getType(): BrokerOperationType
    {
        return $this->type;
    }

    public function getRawType(): string
    {
        return $this->rawType;
    }

    public function getState(): BrokerOperationState
    {
        return $this->state;
    }

    public function getExecutedAt(): \DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function getInstrumentUid(): ?string
    {
        return $this->instrumentUid;
    }

    public function getInstrumentKind(): ?InstrumentKind
    {
        return $this->instrumentKind;
    }

    public function getTicker(): ?string
    {
        return $this->ticker;
    }

    public function getClassCode(): ?string
    {
        return $this->classCode;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return (int) $this->quantity;
    }

    /**
     * @return numeric-string|null
     */
    public function getPrice(): ?string
    {
        return $this->price;
    }

    /**
     * @return numeric-string
     */
    public function getPayment(): string
    {
        return $this->payment;
    }

    public function getCurrency(): string
    {
        return $this->currency;
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
    public function getAccruedInterest(): ?string
    {
        return $this->accruedInterest;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
