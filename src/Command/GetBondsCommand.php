<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Bond;
use App\Services\AccountCalculator;
use App\Services\MarketData\Securities\MoexBondsProvider;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'securities:get-moex-bonds',
    description: 'Get Moex Bonds',
)]
class GetBondsCommand extends Command
{
    public function __construct(
        private readonly MoexBondsProvider $bondsProvider,
        private readonly EntityManagerInterface $em,
        private readonly AccountCalculator $accountCalculator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $bondRepository = $this->em->getRepository(Bond::class);

        ini_set('memory_limit', '1G');

        $bonds = $this->bondsProvider->getBonds();
        foreach ($bonds as $item) {
            $bond = $bondRepository->findOneBy(['ticker' => $item->getTicker()]);
            if ($bond) {
                $updatePeriodStart = Carbon::now()->subMinutes(5);
                $updated = $bond->updatedAt();

                // If the share has been updated by another service, skip this update
                if($updatePeriodStart->diffInMinutes($updated) < 5) {
                    continue;
                }

                if (empty($item->getPrice())) {
                    continue;
                }
                $bond->setPrice($item->getPrice());
                $bond->setPrevPrice($item->getPrevPrice());
                $bond->setName($item->getName());
                $bond->setLatName($item->getLatName());
                $bond->setShortName($item->getShortName());
                $bond->setCouponPercent($item->getCouponPercent());
                $bond->setCouponValue($item->getCouponValue());
                $bond->setCouponAccumulated($item->getCouponAccumulated());
                $bond->setNextCouponDate($item->getNextCouponDate());
                $bond->setMaturityDate($item->getMaturityDate());
                $bond->setLotSize($item->getLotSize());
            } else {
                $bond = new Bond(
                    $item->getTicker(),
                    $item->getName(),
                    $item->getStockMarket(),
                    $item->getCurrency(),
                    $item->getPrice(),
                    $item->getPrevPrice(),
                    $item->getShortName(),
                    $item->getLatName(),
                    $item->getLotSize(),
                    $item->getStepPrice(),
                    $item->getCouponPercent(),
                    $item->getCouponValue(),
                    $item->getCouponAccumulated(),
                    $item->getNextCouponDate(),
                    $item->getMaturityDate(),
                );
            }
            $this->em->persist($bond);
            $this->em->flush();
        }

        $this->accountCalculator->recalculateBalanceForAllAccounts();

        $io->success('Success');

        return Command::SUCCESS;
    }
}
