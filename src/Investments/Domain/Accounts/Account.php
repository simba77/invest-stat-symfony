<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Instrument;
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

    /**
     * Money by currency; for a synced account as the broker reports it.
     *
     * @var Collection<int, AccountCash>
     */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: AccountCash::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $cash;

    /**
     * Since when the deals and the cash of a manual account are rebuilt from its journal;
     * null while they are as entered or as the broker sync left them.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $journalStartedAt = null;

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
        $this->cash = new ArrayCollection();
        $this->userId = $userId;
        $this->name = $name;
        $this->setCash('RUB', $balance);
        $this->setCash('USD', $usdBalance);
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

    /**
     * @return numeric-string
     */
    public function getCash(string $currency): string
    {
        foreach ($this->cash as $cash) {
            if ($cash->getCurrency() === $currency) {
                return $cash->getAmount();
            }
        }

        return '0.0000';
    }

    /**
     * @return array<string, numeric-string> by currency
     */
    public function getCashByCurrency(): array
    {
        $result = [];
        foreach ($this->cash as $cash) {
            $result[$cash->getCurrency()] = $cash->getAmount();
        }

        return $result;
    }

    /**
     * @param numeric-string $amount
     */
    public function setCash(string $currency, string $amount): static
    {
        foreach ($this->cash as $cash) {
            if ($cash->getCurrency() === $currency) {
                $cash->setAmount($amount);

                return $this;
            }
        }
        $this->cash->add(new AccountCash($this, $currency, $amount));

        return $this;
    }

    /**
     * @return numeric-string
     */
    public function getBalance(): string
    {
        return $this->getCash('RUB');
    }

    /**
     * @param numeric-string|null $balance
     */
    public function setBalance(?string $balance): static
    {
        return $this->setCash('RUB', $balance ?? '0');
    }

    /**
     * @return numeric-string
     */
    public function getUsdBalance(): string
    {
        return $this->getCash('USD');
    }

    /**
     * @param numeric-string|null $usdBalance
     */
    public function setUsdBalance(?string $usdBalance): static
    {
        return $this->setCash('USD', $usdBalance ?? '0');
    }

    public function getJournalStartedAt(): ?\DateTimeImmutable
    {
        return $this->journalStartedAt;
    }

    public function startJournal(\DateTimeImmutable $at): void
    {
        $this->journalStartedAt = $at;
    }

    /**
     * The broker sync takes over the records; a journal starts again from them once unlinked.
     */
    public function stopJournal(): void
    {
        $this->journalStartedAt = null;
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

    /**
     * What the broker charges for a trade by the tariff of the account: a percent of the money of a
     * share or a bond trade (a bond by its nominal times the price in percent), a fixed sum per future.
     *
     * @param numeric-string $price as the security is quoted
     * @return numeric-string rounded to kopecks
     */
    public function tradeCommission(?Instrument $instrument, string $price, int $quantity): string
    {
        if ($instrument instanceof Future) {
            return bcround(bcmul($this->getFuturesCommission(), (string) $quantity, 4), 2);
        }

        $money = bcmul($price, (string) $quantity, 9);
        $nominal = $instrument instanceof Bond ? $instrument->getLotSize() : null;
        if ($nominal !== null) {
            $money = bcdiv(bcmul($money, $nominal, 9), '100', 9);
        }

        return bcround(bcdiv(bcmul($money, $this->getCommission(), 9), '100', 9), 2);
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
