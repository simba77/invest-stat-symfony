<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Investment;
use App\Investments\Infrastructure\Persistence\Repository\AccountRepository;
use App\Shared\Domain\CreatedByProvider;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\CreatedUserProviderInterface;
use App\Shared\Domain\UpdatedByProvider;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use App\Shared\Domain\UpdatedUserProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
class Account implements
    CreatedUserProviderInterface,
    UpdatedUserProviderInterface,
    CreatedDateProviderInterface,
    UpdatedDateProviderInterface
{
    use CreatedByProvider;
    use UpdatedByProvider;
    use CreatedDateProvider;
    use UpdatedDateProvider;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $userId;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(nullable: true)]
    private ?int $sort = null;

    /**
     * @psalm-suppress UnusedProperty
     * @var Collection<int, Investment>
     */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Investment::class)]
    private Collection $investments;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $balance = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $usdBalance = null;

    /**
     * @psalm-suppress UnusedProperty
     * @var numeric-string|null
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $startSumOfAssets = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $currentSumOfAssets = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $commission = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $futuresCommission = null;

    /**
     * @psalm-suppress UnusedProperty
     * @var Collection<int, Deal>
     */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Deal::class)]
    private Collection $deals;

    /**
     * @param int $userId
     * @param string $name
     * @param numeric-string $balance
     * @param numeric-string $usdBalance
     * @param numeric-string $commission
     * @param numeric-string $futuresCommission
     * @param int $sort
     */
    public function __construct(
        int $userId,
        string $name,
        string $balance = '0',
        string $usdBalance = '0',
        string $commission = '0',
        string $futuresCommission = '0',
        int $sort = 100
    ) {
        $this->investments = new ArrayCollection();
        $this->userId = $userId;
        $this->name = $name;
        $this->balance = $balance;
        $this->usdBalance = $usdBalance;
        $this->commission = $commission;
        $this->futuresCommission = $futuresCommission;
        $this->sort = $sort;
        $this->deals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(?int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getBalance(): string
    {
        return $this->balance ?? '0';
    }

    public function setBalance(?string $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getUsdBalance(): string
    {
        return $this->usdBalance ?? '0';
    }

    public function setUsdBalance(?string $usdBalance): static
    {
        $this->usdBalance = $usdBalance;

        return $this;
    }

    public function setStartSumOfAssets(?string $startSumOfAssets): static
    {
        $this->startSumOfAssets = $startSumOfAssets;

        return $this;
    }

    public function getCurrentSumOfAssets(): string
    {
        return $this->currentSumOfAssets ?? '0';
    }

    public function setCurrentSumOfAssets(?string $currentSumOfAssets): static
    {
        $this->currentSumOfAssets = $currentSumOfAssets;

        return $this;
    }

    /**
     * @return numeric-string
     */
    public function getCommission(): string
    {
        return $this->commission ?? '0';
    }

    public function setCommission(?string $commission): static
    {
        $this->commission = $commission;

        return $this;
    }

    public function getFuturesCommission(): string
    {
        return $this->futuresCommission ?? '0';
    }

    public function setFuturesCommission(?string $futuresCommission): static
    {
        $this->futuresCommission = $futuresCommission;

        return $this;
    }
}
