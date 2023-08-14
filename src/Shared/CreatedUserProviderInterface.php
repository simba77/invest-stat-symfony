<?php

declare(strict_types=1);

namespace App\Shared;

use App\Entity\User;

/**
 * Каждый объект реализующий этот интерфейс имеет возможность получить данные пользователя создавшего запись в базе данных
 */
interface CreatedUserProviderInterface
{
    public function createdBy(): ?int;

    public function wasCreatedBy(User $user): void;
}
