<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Metaseller\TinkoffInvestApi2\TinkoffClientsFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tinkoff\Invest\V1\GetLastPricesRequest;
use Tinkoff\Invest\V1\LastPrice;

#[AsCommand(
    name: 'securities:get-market-data',
    description: 'Get market data from tinkoff invest api',
)]
class TInvestGetMarketData extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParameterBagInterface $parameters,
        private readonly DealRepositoryInterface $dealRepository,
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

        /** @var string $token */
        $token = $this->parameters->get('app.tinkoff.apiKey');
        $client = TinkoffClientsFactory::create($token);

        $shareRepository = $this->em->getRepository(Share::class);

        $instrumentsRequest = new GetLastPricesRequest();
        $instrumentsRequest->setInstrumentId($uids);

        [$response] = $client->marketDataServiceClient->GetLastPrices($instrumentsRequest)->wait();

        /**
         * @var \Tinkoff\Invest\V1\GetLastPricesResponse $response
         */
        foreach ($response->getLastPrices() as $item) {
            /** @var LastPrice $item */
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
