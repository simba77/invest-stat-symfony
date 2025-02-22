<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\Deal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'deals:convert',
    description: 'Convert the deals table to the new format',
)]
class ConvertDealsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $bondRepository = $this->em->getRepository(Bond::class);
        $sharesRepository = $this->em->getRepository(Share::class);
        $futuresRepository = $this->em->getRepository(Future::class);
        $dealsRepository = $this->em->getRepository(Deal::class);

        $deals = $dealsRepository->findAll();

        foreach ($deals as $deal) {
            $share = $sharesRepository->findOneBy(['ticker' => $deal->getTicker(), 'stockMarket' => $deal->getStockMarket()]);
            $bond = $bondRepository->findOneBy(['ticker' => $deal->getTicker(), 'stockMarket' => $deal->getStockMarket()]);
            $future = $futuresRepository->findOneBy(['ticker' => $deal->getTicker(), 'stockMarket' => $deal->getStockMarket()]);

            $deal->setShare($share);
            $deal->setBond($bond);
            $deal->setFuture($future);

            $this->em->persist($deal);
        }

        $this->em->flush();


        $io->success('Success');

        return Command::SUCCESS;
    }
}
