<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Каждый объект реализующий этот интерфейс имеет возможность получить данные пользователя создавшего запись в базе данных
 */
interface UpdatedUserProviderInterface
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function updatedBy(): ?int;

    public function wasUpdatedBy(User $user): void;
}
