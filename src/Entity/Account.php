<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccountRepository;
use App\Shared\CreatedByProvider;
use App\Shared\CreatedDateProvider;
use App\Shared\CreatedDateProviderInterface;
use App\Shared\CreatedUserProviderInterface;
use App\Shared\UpdatedByProvider;
use App\Shared\UpdatedDateProvider;
use App\Shared\UpdatedDateProviderInterface;
use App\Shared\UpdatedUserProviderInterface;
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
    private ?int $userId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $sort = null;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Investment::class)]
    private Collection $investments;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $balance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $usdBalance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $startSumOfAssets = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $currentSumOfAssets = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?float $commission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?float $futuresCommission = null;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Deal::class)]
    private Collection $deals;

    public function __construct(
        int $userId,
        string $name,
        float $balance = 0,
        float $usdBalance = 0,
        float $commission = 0,
        float $futuresCommission = 0,
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

    public function getBalance(): float
    {
        return $this->balance ?? 0;
    }

    public function setBalance(?float $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getUsdBalance(): float
    {
        return $this->usdBalance ?? 0;
    }

    public function setUsdBalance(?float $usdBalance): static
    {
        $this->usdBalance = $usdBalance;

        return $this;
    }

    public function getStartSumOfAssets(): float
    {
        return $this->startSumOfAssets ?? 0;
    }

    public function setStartSumOfAssets(?float $startSumOfAssets): static
    {
        $this->startSumOfAssets = $startSumOfAssets;

        return $this;
    }

    public function getCurrentSumOfAssets(): float
    {
        return $this->currentSumOfAssets ?? 0;
    }

    public function setCurrentSumOfAssets(?float $currentSumOfAssets): static
    {
        $this->currentSumOfAssets = $currentSumOfAssets;

        return $this;
    }

    public function getCommission(): float
    {
        return $this->commission ?? 0;
    }

    public function setCommission(?float $commission): static
    {
        $this->commission = $commission;

        return $this;
    }

    public function getFuturesCommission(): float
    {
        return $this->futuresCommission ?? 0;
    }

    public function setFuturesCommission(?float $futuresCommission): static
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
        if (!$this->deals->contains($deal)) {
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
