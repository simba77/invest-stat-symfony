<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\Deal;
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
        private readonly ParameterBagInterface $parameters
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dealsRepository = $this->em->getRepository(Deal::class);
        $activeTickers = $dealsRepository->getAllActiveFigi();

        if (empty($activeTickers)) {
            $io->success('No active deals were found!');
            return Command::SUCCESS;
        }

        $token = $this->parameters->get('app.tinkoff.apiKey');
        $client = TinkoffClientsFactory::create($token);

        $shareRepository = $this->em->getRepository(Share::class);

        $instrumentsRequest = new GetLastPricesRequest();
        /** @var iterable<string> $figi */
        $figi = array_column($activeTickers, 'figi');
        $instrumentsRequest->setInstrumentId($figi);

        [$response] = $client->marketDataServiceClient->GetLastPrices($instrumentsRequest)->wait();

        /**
         * @var \Tinkoff\Invest\V1\GetLastPricesResponse $response
         */
        // @phpstan-ignore-next-line
        foreach ($response->getLastPrices() as $item) {
            if(empty($item->getFigi())) {
                continue;
            }

            /** @var LastPrice $item */
            $share = $shareRepository->findOneBy(['figi' => $item->getFigi()]);
            $price = $item->getPrice()->getUnits() . '.' . $item->getPrice()->getNano();
            if ($price > 0) {
                $share->setPrice($price);
                $this->em->persist($share);
            }
            $io->success($share->getStockMarket() . ':' . $share->getTicker() . ' - ' . $share->getPrice());
        }

        $this->em->flush();

        $io->success('Success!');

        return Command::SUCCESS;
    }
}
