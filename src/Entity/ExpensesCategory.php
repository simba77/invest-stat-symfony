<?php

namespace App\Entity;

use App\Repository\ExpensesCategoryRepository;
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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExpensesCategoryRepository::class)]
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
    private ?int $userId = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['expensesForm'])]
    private ?string $name = null;

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
