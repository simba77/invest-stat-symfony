<?php

namespace App\Entity;

use App\Repository\ExpenseRepository;
use App\Shared\CreatedDateProvider;
use App\Shared\CreatedDateProviderInterface;
use App\Shared\UpdatedDateProvider;
use App\Shared\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
class Expense implements CreatedDateProviderInterface, UpdatedDateProviderInterface
{
    use CreatedDateProvider;
    use UpdatedDateProvider;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2)]
    private ?float $sum = null;

    #[ORM\Column]
    private ?int $category_id = null;

    #[ORM\Column(nullable: true)]
    private ?int $created_by = null;

    #[ORM\Column(nullable: true)]
    private ?int $updated_by = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

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

    public function setSum(float $sum): static
    {
        $this->sum = $sum;

        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->category_id;
    }

    public function setCategoryId(int $category_id): static
    {
        $this->category_id = $category_id;

        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->created_by;
    }

    public function setCreatedBy(?int $created_by): self
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updated_by;
    }

    public function setUpdatedBy(?int $updated_by): self
    {
        $this->updated_by = $updated_by;

        return $this;
    }
}
