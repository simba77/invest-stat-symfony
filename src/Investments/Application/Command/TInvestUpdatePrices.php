<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Domain\Instruments\BondRepositoryInterface;
use App\Investments\Domain\Instruments\FutureRepositoryInterface;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Infrastructure\Http\TInvestHttpClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'securities:update-prices',
    description: 'Get prices from tinkoff invest api',
)]
class TInvestUpdatePrices extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DealRepositoryInterface $dealRepository,
        private readonly TInvestHttpClient $httpClient,
        private readonly ShareRepositoryInterface $shareRepository,
        private readonly BondRepositoryInterface $bondRepository,
        private readonly FutureRepositoryInterface $futureRepository,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->updateSharePrices($io);
        $this->updateBondPrices($io);
        $this->updateFuturePrices($io);

        return Command::SUCCESS;
    }

    private function updateSharePrices(SymfonyStyle $io): void
    {
        $uids = [];
        $deals = $this->dealRepository->getAllActiveDealsWithSharesAndTUid();
        foreach ($deals as $deal) {
            $uid = $deal->getShare()?->getTUid() ?? null;
            if ($uid !== null) {
                $uids[] = $uid;
            }
        }

        if (empty($uids)) {
            $io->success('No active deals with shares were found!');
            return;
        }

        $prices = $this->httpClient->getLastPricesByUids($uids);
        foreach ($prices as $item) {
            if (empty($item->getInstrumentUid())) {
                continue;
            }

            $share = $this->shareRepository->findByTUid($item->getInstrumentUid());
            if ($share === null) {
                continue;
            }

            $price = sprintf('%s.%s', $item->getPrice()?->getUnits() ?? '0', $item->getPrice()?->getNano() ?? '0');
            if ($price > 0) {
                $share->setPrice($price);
                $this->em->persist($share);
            }
            $io->success(sprintf('%s: %s - %s', $share->getStockMarket(), $share->getTicker(), $share->getPrice()));
        }

        $this->em->flush();

        $io->success('Share prices updated!');
    }

    private function updateBondPrices(SymfonyStyle $io): void
    {
        $uids = [];
        $deals = $this->dealRepository->getAllActiveDealsWithBondsAndTUid();
        foreach ($deals as $deal) {
            $uid = $deal->getBond()?->getTUid() ?? null;
            if ($uid !== null) {
                $uids[] = $uid;
            }
        }
        if (empty($uids)) {
            $io->success('No active deals with bonds were found!');
            return;
        }

        $prices = $this->httpClient->getLastPricesByUids($uids);
        foreach ($prices as $item) {
            if (empty($item->getInstrumentUid())) {
                continue;
            }

            $bond = $this->bondRepository->findByTUid($item->getInstrumentUid());
            if ($bond === null) {
                continue;
            }

            $price = sprintf('%s.%s', $item->getPrice()?->getUnits() ?? '0', $item->getPrice()?->getNano() ?? '0');
            if ($price > 0) {
                $bond->setPrice($price);
                $this->em->persist($bond);
            }
            $io->success(sprintf('%s: %s (%s) - %s', $bond->getStockMarket(), $bond->getName(), $bond->getTicker(), $bond->getPrice()));
        }

        $this->em->flush();
        $io->success('Bond prices updated!');
    }

    private function updateFuturePrices(SymfonyStyle $io): void
    {
        $uids = [];
        $deals = $this->dealRepository->getAllActiveDealsWithFuturesAndTUid();
        foreach ($deals as $deal) {
            $uid = $deal->getFuture()?->getTUid() ?? null;
            if ($uid !== null) {
                $uids[] = $uid;
            }
        }
        if (empty($uids)) {
            $io->success('No active deals with futures were found!');
            return;
        }

        $prices = $this->httpClient->getLastPricesByUids($uids);
        foreach ($prices as $item) {
            if (empty($item->getInstrumentUid())) {
                continue;
            }

            $future = $this->futureRepository->findByTUid($item->getInstrumentUid());
            if ($future === null) {
                continue;
            }

            $price = sprintf('%s.%s', $item->getPrice()?->getUnits() ?? '0', $item->getPrice()?->getNano() ?? '0');
            if ($price > 0) {
                $future->setPrice($price);
                $this->em->persist($future);
            }
            $io->success(sprintf('%s: %s (%s) - %s', $future->getStockMarket(), $future->getName(), $future->getTicker(), $future->getPrice()));
        }

        $this->em->flush();
        $io->success('Bond prices updated!');
    }
}
