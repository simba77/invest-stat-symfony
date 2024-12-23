<?php

namespace App\Domain\Expenses;

use App\Domain\Shared\CreatedByProvider;
use App\Domain\Shared\CreatedDateProvider;
use App\Domain\Shared\CreatedDateProviderInterface;
use App\Domain\Shared\CreatedUserProviderInterface;
use App\Domain\Shared\UpdatedByProvider;
use App\Domain\Shared\UpdatedDateProvider;
use App\Domain\Shared\UpdatedDateProviderInterface;
use App\Domain\Shared\UpdatedUserProviderInterface;
use App\Infrastructure\Persistence\Repository\ExpenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
#[ORM\Table(name: 'expenses')]
class Expense implements
    CreatedDateProviderInterface,
    UpdatedDateProviderInterface,
    CreatedUserProviderInterface,
    UpdatedUserProviderInterface
{
    use CreatedDateProvider;
    use UpdatedDateProvider;
    use CreatedByProvider;
    use UpdatedByProvider;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id')]
    private int $userId;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2)]
    private string $sum;

    #[ORM\ManyToOne(targetEntity: ExpensesCategory::class, inversedBy: 'expenses')]
    private ExpensesCategory $category;

    public function __construct(string $name, string $sum, int $userId)
    {
        $this->name = $name;
        $this->sum = $sum;
        $this->userId = $userId;
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

    public function getSum(): ?string
    {
        return $this->sum;
    }

    public function setSum(string $sum): static
    {
        $this->sum = $sum;

        return $this;
    }

    public function getCategory(): ?ExpensesCategory
    {
        return $this->category;
    }

    public function setCategory(?ExpensesCategory $category): static
    {
        $this->category = $category;

        return $this;
    }
}
