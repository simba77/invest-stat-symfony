<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Future;
use App\Services\AccountCalculator;
use App\Services\MarketData\Securities\MoexFuturesProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'securities:get-moex-futures',
    description: 'Get Moex Futures',
)]
class GetFuturesCommand extends Command
{
    public function __construct(
        private readonly MoexFuturesProvider $futuresProvider,
        private readonly EntityManagerInterface $em,
        private readonly AccountCalculator $accountCalculator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $futureRepository = $this->em->getRepository(Future::class);

        $futures = $this->futuresProvider->getFutures();
        foreach ($futures as $item) {
            $future = $futureRepository->findOneBy(['ticker' => $item->getTicker()]);
            if ($future) {
                if (empty($item->getPrice())) {
                    continue;
                }
                $future->setPrice($item->getPrice());
                $future->setPrevPrice($item->getPrevPrice());
                $future->setName($item->getName());
                $future->setLatName($item->getLatName());
                $future->setShortName($item->getShortName());
            } else {
                $future = new Future(
                    $item->getTicker(),
                    $item->getName(),
                    $item->getStockMarket(),
                    $item->getCurrency(),
                    $item->getPrice(),
                    $item->getPrevPrice(),
                    $item->getShortName(),
                    $item->getLatName(),
                    $item->getLotSize(),
                    $item->getExpiration(),
                    $item->getStepPrice(),
                );
            }
            $this->em->persist($future);
            $this->em->flush();
        }

        $this->accountCalculator->recalculateBalanceForAllAccounts();

        $io->success('Success');

        return Command::SUCCESS;
    }
}
