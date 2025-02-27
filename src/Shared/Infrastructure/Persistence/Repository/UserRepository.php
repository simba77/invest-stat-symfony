<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Repository;

use App\Shared\Domain\User;
use App\Shared\Domain\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\LazyServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends LazyServiceEntityRepository<User>
 * @implements PasswordUpgraderInterface<PasswordAuthenticatedUserInterface>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedParam */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (! $user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?User
    {
        return $this->find($id);
    }
}
