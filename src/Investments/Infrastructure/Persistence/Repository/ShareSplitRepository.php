<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\ShareSplit;
use App\Investments\Domain\Instruments\ShareSplitRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShareSplit>
 */
class ShareSplitRepository extends ServiceEntityRepository implements ShareSplitRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShareSplit::class);
    }

    /**
     * @return list<ShareSplit>
     */
    #[\Override]
    public function findAll(): array
    {
        /** @var list<ShareSplit> */
        return $this->findBy([], ['tradeDate' => 'ASC', 'id' => 'ASC']);
    }

    #[\Override]
    public function save(ShareSplit $split): void
    {
        $em = $this->getEntityManager();
        $em->persist($split);
        $em->flush();
    }
}
