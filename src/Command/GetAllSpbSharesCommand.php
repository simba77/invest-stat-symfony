<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Deal;
use App\Entity\Share;
use App\Http\InvestCabHttpClient;
use App\Services\AccountCalculator;
use App\Services\MarketData\Securities\ShareTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'securities:get-all-spb-shares',
    description: 'Get All SPB Shares (including closed deals)',
)]
class GetAllSpbSharesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InvestCabHttpClient $httpClient,
        private readonly LoggerInterface $logger,
        private readonly AccountCalculator $accountCalculator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $shareRepository = $this->em->getRepository(Share::class);
        $dealsRepository = $this->em->getRepository(Deal::class);

        $tickers = $dealsRepository->getAllTickersByStockMarket('SPB');

        foreach ($tickers as $k => $ticker) {
            if ($k > 0) {
                sleep(rand(1, 5));
            }

            $share = $shareRepository->findOneBy(['ticker' => $ticker['ticker'], 'stockMarket' => 'SPB']);
            try {
                $price = $this->httpClient->getPriceByTicker($ticker['ticker']);

                if ($share) {
                    $share->setPrice($price);
                } else {
                    $tickerData = $this->httpClient->getDataAboutTicker($ticker['ticker']);
                    $share = new Share(
                        $ticker['ticker'],
                        $tickerData['description'] ?? $ticker['ticker'],
                        'SPB',
                        'USD',
                        $price,
                        ShareTypeEnum::Stock->value,
                        $tickerData['description'] ?? $ticker['ticker'],
                        $tickerData['description'] ?? $ticker['ticker'],
                        1,
                        ''
                    );
                }
                $this->em->persist($share);
                $this->em->flush();
            } catch (\Throwable $throwable) {
                $this->logger->warning($throwable->getMessage(), ['e' => $throwable]);
            }
        }

        $this->accountCalculator->recalculateBalanceForAllAccounts();

        $io->success('Success');
        return Command::SUCCESS;
    }
}
