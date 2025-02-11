<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
}
