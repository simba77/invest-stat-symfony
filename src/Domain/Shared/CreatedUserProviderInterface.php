<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Каждый объект реализующий этот интерфейс имеет возможность получить данные пользователя создавшего запись в базе данных
 */
interface CreatedUserProviderInterface
{
    public function createdBy(): ?int;

    public function wasCreatedBy(User $user): void;
}
