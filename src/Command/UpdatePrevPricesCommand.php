<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Bond;
use App\Entity\Future;
use App\Entity\Share;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'securities:update-prev-prices',
    description: 'Update previous prices',
)]
class UpdatePrevPricesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1G');

        $io = new SymfonyStyle($input, $output);

        // Set prev prices for bonds
        $bondRepository = $this->em->getRepository(Bond::class);
        $bonds = $bondRepository->findAll();
        foreach ($bonds as $bond) {
            $bond->setPrevPrice($bond->getPrice());
            $this->em->persist($bond);
        }
        $this->em->flush();

        // Set previous prices for shares
        $sharesRepository = $this->em->getRepository(Share::class);
        $shares = $sharesRepository->findAll();
        foreach ($shares as $share) {
            $share->setPrevPrice($share->getPrice());
            $this->em->persist($share);
        }
        $this->em->flush();

        // Set previous prices for futures
        $futuresRepository = $this->em->getRepository(Future::class);
        $futures = $futuresRepository->findAll();
        foreach ($futures as $future) {
            $future->setPrevPrice($future->getPrice());
            $this->em->persist($future);
        }
        $this->em->flush();


        $io->success('Success');

        return Command::SUCCESS;
    }
}
