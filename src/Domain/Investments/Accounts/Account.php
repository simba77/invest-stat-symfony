<?php

declare(strict_types=1);

namespace App\Domain\Investments\Accounts;

use App\Domain\Investments\Operations\Deal;
use App\Domain\Investments\Operations\Investment;
use App\Domain\Shared\CreatedByProvider;
use App\Domain\Shared\CreatedDateProvider;
use App\Domain\Shared\CreatedDateProviderInterface;
use App\Domain\Shared\CreatedUserProviderInterface;
use App\Domain\Shared\UpdatedByProvider;
use App\Domain\Shared\UpdatedDateProvider;
use App\Domain\Shared\UpdatedDateProviderInterface;
use App\Domain\Shared\UpdatedUserProviderInterface;
use App\Infrastructure\Persistence\Repository\AccountRepository;
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
     * @var Collection<int, Investment>
     */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Investment::class)]
    private Collection $investments;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $balance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $usdBalance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $startSumOfAssets = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $currentSumOfAssets = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $commission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $futuresCommission = null;

    /**
     * @var Collection<int, Deal>
     */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Deal::class)]
    private Collection $deals;

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

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
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

    /**
     * @return Collection<int, Investment>
     */
    public function getInvestments(): Collection
    {
        return $this->investments;
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

    public function getStartSumOfAssets(): string
    {
        return $this->startSumOfAssets ?? '0';
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

    /**
     * @return Collection<int, Deal>
     */
    public function getDeals(): Collection
    {
        return $this->deals;
    }

    public function addDeal(Deal $deal): static
    {
        if (! $this->deals->contains($deal)) {
            $this->deals->add($deal);
            $deal->setAccount($this);
        }

        return $this;
    }

    public function removeDeal(Deal $deal): static
    {
        if ($this->deals->removeElement($deal)) {
            // set the owning side to null (unless already changed)
            if ($deal->getAccount() === $this) {
                $deal->setAccount(null);
            }
        }

        return $this;
    }
}
