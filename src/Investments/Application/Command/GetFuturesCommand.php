<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Securities\MoexFuturesProvider;
use Carbon\Carbon;
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
        private readonly AccountBalanceCalculator $accountBalanceCalculator
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $futureRepository = $this->em->getRepository(Future::class);

        $futures = $this->futuresProvider->getFutures();
        foreach ($futures as $item) {
            $future = $futureRepository->findOneBy(['ticker' => $item->getTicker()]);
            if ($future) {

                $updatePeriodStart = Carbon::now()->subMinutes(5);
                $updated = $future->updatedAt();

                // If the share has been updated by another service, skip this update
                if($updatePeriodStart->diffInMinutes($updated) < 5) {
                    continue;
                }


                if (empty($item->getPrice())) {
                    continue;
                }
                $future->setPrice($item->getPrice());
                $future->setPrevPrice($item->getPrevPrice());
                $future->setName($item->getName());
                $future->setLatName($item->getLatName());
                $future->setShortName($item->getShortName());
                $future->setLotSize($item->getLotSize());
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

        $this->accountBalanceCalculator->recalculateBalanceForAllAccounts();

        $io->success('Success');

        return Command::SUCCESS;
    }
}
