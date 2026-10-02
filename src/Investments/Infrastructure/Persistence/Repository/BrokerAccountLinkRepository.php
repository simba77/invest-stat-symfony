<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerAccountLinkRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BrokerAccountLink>
 */
class BrokerAccountLinkRepository extends ServiceEntityRepository implements BrokerAccountLinkRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BrokerAccountLink::class);
    }

    #[\Override]
    public function findByAccount(Account $account): ?BrokerAccountLink
    {
        return $this->findOneBy(['account' => $account]);
    }

    #[\Override]
    public function save(BrokerAccountLink $link): void
    {
        $em = $this->getEntityManager();
        $em->persist($link);
        $em->flush();
    }

    #[\Override]
    public function remove(BrokerAccountLink $link): void
    {
        $em = $this->getEntityManager();
        $em->remove($link);
        $em->flush();
    }
}
