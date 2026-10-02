<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Infrastructure\Persistence\Repository\BrokerAccountLinkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Connects a local account to an account at a broker, whose operations replace manual input.
 */
#[ORM\Entity(repositoryClass: BrokerAccountLinkRepository::class)]
#[ORM\Table(name: 'broker_account_links')]
class BrokerAccountLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(length: 32, enumType: BrokerProvider::class)]
    private BrokerProvider $provider;

    #[ORM\Column(type: Types::TEXT)]
    private string $encryptedToken;

    #[ORM\Column(length: 64)]
    private string $externalAccountId;

    #[ORM\Column(length: 255)]
    private string $externalAccountName;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $externalAccountOpenedAt;

    #[ORM\Column(length: 32, enumType: FeeAllocation::class)]
    private FeeAllocation $feeAllocation;

    #[ORM\Column]
    private bool $enabled;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\Column(length: 16, nullable: true, enumType: SyncStatus::class)]
    private ?SyncStatus $lastSyncStatus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastSyncMessage = null;

    /** @var list<array{name: string, broker: string, calculated: string}> */
    #[ORM\Column(type: Types::JSON)]
    private array $lastSyncDiscrepancies = [];

    public function __construct(
        Account $account,
        BrokerProvider $provider,
        string $encryptedToken,
        string $externalAccountId,
        string $externalAccountName,
        ?\DateTimeImmutable $externalAccountOpenedAt,
        FeeAllocation $feeAllocation,
        bool $enabled,
    ) {
        $this->account = $account;
        $this->provider = $provider;
        $this->encryptedToken = $encryptedToken;
        $this->externalAccountId = $externalAccountId;
        $this->externalAccountName = $externalAccountName;
        $this->externalAccountOpenedAt = $externalAccountOpenedAt;
        $this->feeAllocation = $feeAllocation;
        $this->enabled = $enabled;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getProvider(): BrokerProvider
    {
        return $this->provider;
    }

    public function getEncryptedToken(): string
    {
        return $this->encryptedToken;
    }

    public function getExternalAccountId(): string
    {
        return $this->externalAccountId;
    }

    public function getExternalAccountName(): string
    {
        return $this->externalAccountName;
    }

    public function getExternalAccountOpenedAt(): ?\DateTimeImmutable
    {
        return $this->externalAccountOpenedAt;
    }

    public function getFeeAllocation(): FeeAllocation
    {
        return $this->feeAllocation;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function getLastSyncStatus(): ?SyncStatus
    {
        return $this->lastSyncStatus;
    }

    public function getLastSyncMessage(): ?string
    {
        return $this->lastSyncMessage;
    }

    /**
     * @return list<array{name: string, broker: string, calculated: string}>
     */
    public function getLastSyncDiscrepancies(): array
    {
        return $this->lastSyncDiscrepancies;
    }

    public function replaceToken(string $encryptedToken): void
    {
        $this->encryptedToken = $encryptedToken;
    }

    /**
     * Points the link to another external account. Returns true when the account changed:
     * the operations synced so far belong to the previous one and have to be dropped.
     */
    public function reconfigure(
        BrokerProvider $provider,
        string $externalAccountId,
        string $externalAccountName,
        ?\DateTimeImmutable $externalAccountOpenedAt,
        FeeAllocation $feeAllocation,
        bool $enabled,
    ): bool {
        $accountChanged = $provider !== $this->provider || $externalAccountId !== $this->externalAccountId;

        $this->provider = $provider;
        $this->externalAccountId = $externalAccountId;
        $this->externalAccountName = $externalAccountName;
        $this->externalAccountOpenedAt = $externalAccountOpenedAt;
        $this->feeAllocation = $feeAllocation;
        $this->enabled = $enabled;

        if ($accountChanged) {
            $this->lastSyncedAt = null;
            $this->lastSyncStatus = null;
            $this->lastSyncMessage = null;
            $this->lastSyncDiscrepancies = [];
        }

        return $accountChanged;
    }

    /**
     * @param list<array{name: string, broker: string, calculated: string}> $discrepancies
     */
    public function markSynced(\DateTimeImmutable $syncedAt, array $discrepancies, ?string $message = null): void
    {
        $this->lastSyncedAt = $syncedAt;
        $this->lastSyncStatus = $discrepancies === [] && $message === null ? SyncStatus::Success : SyncStatus::Warning;
        $this->lastSyncMessage = $message;
        $this->lastSyncDiscrepancies = $discrepancies;
    }

    /**
     * Keeps the time of the last successful sync: the next sync continues from there.
     */
    public function markFailed(string $message): void
    {
        $this->lastSyncStatus = SyncStatus::Failed;
        $this->lastSyncMessage = $message;
    }
}
