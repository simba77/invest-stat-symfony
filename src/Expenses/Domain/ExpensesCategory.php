<?php

declare(strict_types=1);

namespace App\Expenses\Domain;

use App\Expenses\Infrastructure\Persistence\Repository\ExpensesCategoryRepository;
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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExpensesCategoryRepository::class)]
#[ORM\Table(name: 'expense_categories')]
class ExpensesCategory implements
    CreatedUserProviderInterface,
    CreatedDateProviderInterface,
    UpdatedUserProviderInterface,
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

    #[ORM\Column(name: 'user_id')]
    private int $userId;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['expensesForm'])]
    private string $name;

    /**
     * @var Collection<int, Expense>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Expense::class, fetch: 'EAGER')]
    private Collection $expenses;

    public function __construct(string $name, ?int $userId)
    {
        $this->expenses = new ArrayCollection();
        $this->name = $name;
        $this->userId = $userId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Expense>
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }
}
