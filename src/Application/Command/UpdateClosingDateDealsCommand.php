<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Investments\Operations\Deal;
use App\Domain\Investments\Operations\Deals\DealStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'deals:update-closing-date',
    description: 'Move closing date to new field',
)]
class UpdateClosingDateDealsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dealsRepo = $this->entityManager->getRepository(Deal::class);
        /** @var Deal[] $deals */
        $deals = $dealsRepo->createQueryBuilder('deal')
            ->where('deal.status = :status')
            ->andWhere('deal.updatedAt IS NOT NULL')
            ->setParameter('status', DealStatus::Closed)
            ->getQuery()
            ->execute();

        foreach ($deals as $deal) {
            $deal->setClosingDate($deal->updatedAt());
            $this->entityManager->persist($deal);
        }
        $this->entityManager->flush();

        $io->success('Success');

        return Command::SUCCESS;
    }
}
