<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\BondRepositoryInterface;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\FutureRepositoryInterface;
use App\Investments\Domain\Instruments\Securities\ShareTypeEnum;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;
use App\Investments\Infrastructure\Http\TInvestHttpClient;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'securities:update-instruments',
    description: 'Update instruments data from tinkoff api',
)]
class TInvestUpdateInstruments extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
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

        $this->updateShares();
        $this->updateBonds();
        $this->updateFutures();

        $io->success('Success!');

        return Command::SUCCESS;
    }

    private function updateShares(): void
    {
        $shares = $this->httpClient->getAllShares();
        foreach ($shares as $item) {
            if ($item->getExchange() === 'spb_close' || $item->getExchange() === 'otc_ncc') {
                continue;
            }

            $stockMarket = 'SPB';
            if (preg_match('/(moex|spb)/is', $item->getExchange(), $matches)) {
                $stockMarket = strtoupper($matches[0]);
            }

            $share = $this->shareRepository->findByTickerAndStockMarket($item->getTicker(), $stockMarket);
            if ($share) {
                $share->setName($item->getName());
                $share->setIsin($item->getIsin());
                $share->setLotSize((string) $item->getLot());
            } else {
                $this->logger->info('Share not found: {exchange} {ticker}. Creating new one', [
                    'exchange' => $item->getExchange(),
                    'ticker'   => $item->getTicker(),
                    'name'     => $item->getName(),
                    'uid'      => $item->getUid(),
                    'currency' => $item->getCurrency(),
                ]);

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
            $share->setTUID($item->getUid());
            $share->setClassCode($item->getClassCode());
            $this->em->persist($share);
        }

        $this->em->flush();
    }

    public function updateBonds(): void
    {
        $bonds = $this->httpClient->getAllBonds();
        foreach ($bonds as $item) {
            $exchanges = ['MOEX', 'moex_plus_bonds', 'moex_morning_evening_ofz'];
            if (! in_array($item->getExchange(), $exchanges)) {
                continue;
            }

            $bond = $this->bondRepository->findByTickerAndStockMarket($item->getTicker(), 'MOEX');
            if (! $bond) {
                $this->logger->info(
                    'Bond not found: {ticker} {name}. Creating new one',
                    [
                        'exchange' => $item->getTicker(),
                        'ticker'   => $item->getName(),
                    ]
                );

                $bond = new Bond(
                    ticker:            $item->getTicker(),
                    name:              $item->getName(),
                    stockMarket:       'MOEX',
                    currency:          strtoupper($item->getCurrency()),
                    price:             '0',
                    prevPrice:         '0',
                    shortName:         $item->getName(),
                    latName:           '',
                    lotSize:           sprintf('%s.%s', $item->getNominal()?->getUnits() ?? '0', $item->getNominal()?->getNano() ?? '0'),
                    stepPrice:         sprintf('%s.%s', $item->getMinPriceIncrement()?->getUnits() ?? '0', $item->getMinPriceIncrement()?->getNano() ?? '0'),
                    couponPercent:     '0',
                    couponValue:       '0',
                    couponAccumulated: sprintf('%s.%s', $item->getAciValue()?->getUnits() ?? '0', $item->getAciValue()?->getNano() ?? '0'),
                );
            }

            $bond->setTUid($item->getUid());
            $this->em->persist($bond);
        }

        $this->em->flush();
    }

    public function updateFutures(): void
    {
        $futures = $this->httpClient->getAllFutures();
        foreach ($futures as $item) {
            if ($item->getExchange() !== 'FORTS_EVENING') {
                continue;
            }

            $future = $this->futureRepository->findByTickerAndStockMarket($item->getTicker(), 'MOEX');
            if (! $future) {
                $this->logger->info(
                    'Futures not found: {ticker} {name}. Creating new one',
                    [
                        'exchange' => $item->getTicker(),
                        'ticker'   => $item->getName(),
                    ]
                );

                $expiration = $item->getExpirationDate()?->getSeconds();
                if ($expiration !== null) {
                    $expiration = Carbon::createFromTimestamp($expiration)->toDateTimeImmutable();
                }

                $future = new Future(
                    ticker:      $item->getTicker(),
                    name:        $item->getName(),
                    stockMarket: 'MOEX',
                    currency:    strtoupper($item->getCurrency()),
                    price:       '0',
                    prevPrice:   '0',
                    shortName:   $item->getName(),
                    latName:     '',
                    lotSize:     (string) $item->getLot(),
                    expiration:  $expiration,
                    stepPrice:   sprintf('%s.%s', $item->getMinPriceIncrement()?->getUnits() ?? '0', $item->getMinPriceIncrement()?->getNano() ?? '0'),
                );
            }

            $future->setTUid($item->getUid());
            $this->em->persist($future);
        }

        $this->em->flush();
    }
}
