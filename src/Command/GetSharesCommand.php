<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Share;
use App\Services\MarketData\Securities\MoexSharesProvider;
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
class GetSharesCommand extends Command
{
    public function __construct(
        private readonly MoexSharesProvider $sharesProvider,
        private readonly EntityManagerInterface $em,
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
                $share->setPrice($item->getPrice());
                $share->setName($item->getName());
                $share->setLatName($item->getLatName());
                $share->setShortName($item->getShortName());
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
                    $item->getIsin()
                );
            }
            $this->em->persist($share);
            $this->em->flush();
        }

        $io->success('Success');

        return Command::SUCCESS;
    }
}