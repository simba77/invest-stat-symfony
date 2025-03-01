<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Infrastructure\Http\TInvestHttpClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'securities:get-market-data',
    description: 'Get market data from tinkoff invest api',
)]
class TInvestGetMarketData extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DealRepositoryInterface $dealRepository,
        private readonly TInvestHttpClient $httpClient,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $uids = [];
        $deals = $this->dealRepository->getAllActiveDealsWithTUid();
        foreach ($deals as $deal) {
            $uid = $deal->getShare()?->getTUid() ?? null;
            if ($uid !== null) {
                $uids[] = $uid;
            }
        }

        if (empty($uids)) {
            $io->success('No active deals were found!');
            return Command::SUCCESS;
        }

        $shareRepository = $this->em->getRepository(Share::class);
        $prices = $this->httpClient->getLastPricesByUids($uids);

        foreach ($prices as $item) {
            if (empty($item->getInstrumentUid())) {
                continue;
            }

            $share = $shareRepository->findOneBy(['tUid' => $item->getInstrumentUid()]);
            if ($share === null) {
                continue;
            }

            $price = sprintf('%s.%s', ((string) $item->getPrice()?->getUnits()), ((string) $item->getPrice()?->getNano()));
            if ($price > 0) {
                $share->setPrice($price);
                $this->em->persist($share);
            }
            $io->success(sprintf('%s: %s - %s', $share->getStockMarket() ?? '', $share->getTicker() ?? '', $share->getPrice() ?? ''));
        }

        $this->em->flush();

        $io->success('Success!');

        return Command::SUCCESS;
    }
}
