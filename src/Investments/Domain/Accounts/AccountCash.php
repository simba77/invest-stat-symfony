<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * The money of an account in one currency.
 */
#[ORM\Entity]
#[ORM\Table(name: 'account_cash')]
#[ORM\UniqueConstraint(name: 'account_cash_currency', columns: ['account_id', 'currency'])]
class AccountCash
{
    /** @psalm-suppress UnusedProperty */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @param numeric-string $amount
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'cash')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Account $account,
        #[ORM\Column(length: 8)]
        private string $currency,
        #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
        private string $amount,
    ) {
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return numeric-string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param numeric-string $amount
     */
    public function setAmount(string $amount): void
    {
        // The same value as a string of another scale is not a change
        if (bccomp($this->amount, $amount, 4) !== 0) {
            $this->amount = $amount;
        }
    }
}
