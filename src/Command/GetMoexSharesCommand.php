<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Share;
use App\Services\AccountCalculator;
use App\Services\MarketData\Securities\MoexSharesProvider;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'securities:get-moex-shares',
    description: 'Get Moex Shares',
)]
class GetMoexSharesCommand extends Command
{
    public function __construct(
        private readonly MoexSharesProvider $sharesProvider,
        private readonly EntityManagerInterface $em,
        private readonly AccountCalculator $accountCalculator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $shareRepository = $this->em->getRepository(Share::class);

        $shares = $this->sharesProvider->getShares();
        foreach ($shares as $item) {
            $share = $shareRepository->findOneBy(['ticker' => $item->getTicker()]);
            if ($share) {

                $updatePeriodStart = Carbon::now()->subMinutes(5);
                $updated = $share->updatedAt();

                // If the share has been updated by another service, skip this update
                if($updatePeriodStart->diffInMinutes($updated) < 5) {
                    continue;
                }

                if (empty($item->getPrice())) {
                    continue;
                }
                $share->setPrice($item->getPrice());
                $share->setName($item->getName());
                $share->setLatName($item->getLatName());
                $share->setShortName($item->getShortName());
                $share->setPrevPrice((string) $item->getPrevPrice());
                $share->setLotSize($item->getLotSize());
            } else {
                $share = new Share(
                    $item->getTicker(),
                    $item->getName(),
                    $item->getStockMarket(),
                    $item->getCurrency(),
                    $item->getPrice(),
                    $item->getType(),
                    $item->getShortName(),
                    $item->getLatName(),
                    $item->getLotSize(),
                    $item->getIsin(),
                    (string) $item->getPrevPrice(),
                );
            }

            $share->setClassCode($item->getClassCode());

            $this->em->persist($share);
            $this->em->flush();
        }

        $this->accountCalculator->recalculateBalanceForAllAccounts();

        $io->success('Success');

        return Command::SUCCESS;
    }
}
