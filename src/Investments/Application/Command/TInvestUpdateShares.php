<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Domain\Instruments\Securities\ShareTypeEnum;
use App\Investments\Domain\Instruments\Share;
use Doctrine\ORM\EntityManagerInterface;
use Metaseller\TinkoffInvestApi2\TinkoffClientsFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tinkoff\Invest\V1\InstrumentsRequest;

#[AsCommand(
    name: 'securities:get-tinvest-shares',
    description: 'Update shares data from tinkoff api',
)]
class TInvestUpdateShares extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $parameters
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $token = $this->parameters->get('app.tinkoff.apiKey');
        $client = TinkoffClientsFactory::create($token);

        $shareRepository = $this->em->getRepository(Share::class);

        $instrumentsRequest = new InstrumentsRequest();
        [$response] = $client->instrumentsServiceClient->Shares($instrumentsRequest)->wait();
        /** @var \Tinkoff\Invest\V1\SharesResponse $response */
        // @phpstan-ignore-next-line
        foreach ($response->getInstruments() as $item) {
            /** @var \Tinkoff\Invest\V1\Share $item */
            if ($item->getExchange() === 'spb_close' || $item->getExchange() === 'otc_ncc') {
                continue;
            }

            $stockMarket = 'SPB';
            if (preg_match('/(moex|spb)/is', $item->getExchange(), $matches)) {
                $stockMarket = strtoupper($matches[0]);
            }

            $share = $shareRepository->findOneBy(['ticker' => $item->getTicker(), 'stockMarket' => $stockMarket]);
            if ($share) {
                $share->setName($item->getName());
                $share->setIsin($item->getIsin());
                $share->setLotSize((string) $item->getLot());
            } else {
                $this->logger->info('Instrument not found: ' . $item->getTicker(), [$item->getExchange(), $item->getName(), $item->getTicker(), $item->getFigi(), $item->getCurrency()]);

                $share = new Share(
                    $item->getTicker(),
                    $item->getName(),
                    $stockMarket,
                    strtoupper($item->getCurrency()),
                    '0',
                    ShareTypeEnum::Stock->value,
                    $item->getName(),
                    $item->getName(),
                    (string) $item->getLot(),
                    $item->getIsin(),
                    '0',
                );
            }

            $share->setSector($item->getSector());
            $share->setFigi($item->getFigi());
            $share->setClassCode($item->getClassCode());
            $this->em->persist($share);
        }

        $this->em->flush();

        $io->success('Success!');

        return Command::SUCCESS;
    }
}
