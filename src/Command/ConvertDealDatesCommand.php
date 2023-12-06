<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Deal;
use Carbon\Carbon;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'convert-deal-dates',
    description: 'Update deal dates from old database to new one',
)]
class ConvertDealDatesCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->convertDeals();

        $io->success('Success');

        return Command::SUCCESS;
    }

    private function convertDeals(): void
    {
        /** @var Connection $connection */
        $connection = $this->managerRegistry->getConnection('old_database');

        $deals = $this->entityManager->getRepository(Deal::class)->findBy([], ['id' => 'ASC']);
        $assets = $connection->executeQuery('SELECT assets.* FROM `assets` ORDER BY assets.id')->fetchAllAssociative();

        foreach ($assets as $k => $asset) {
            if (! array_key_exists($k, $deals)) {
                echo 'DEAL NOT FOUND - ' . $asset['id'] . ' - ' . $asset['ticker'] . ' - ' . $asset['quantity'] . PHP_EOL;
                continue;
            }

            $deal = $deals[$k];
            if ($asset['ticker'] === $deal->getTicker() && $asset['quantity'] == $deal->getQuantity()) {
                echo 'ok - ' . $asset['ticker'] . ' - ' . $asset['quantity'] .' - ' . $asset['updated_at'] . PHP_EOL;

                $deal->wasCreatedAt(Carbon::parse($asset['created_at']));
                if ($asset['status'] == 1) {
                    $deal->setClosingDate(Carbon::parse($asset['updated_at']));
                }

                $this->entityManager->persist($deal);
            } else {
                echo 'ERROR - ' . $asset['id'] . ' - ' . $asset['ticker'] . ' - ' . $asset['quantity'] . ' : ' . $deal->getQuantity() . PHP_EOL;
            }
        }

        $this->entityManager->flush();
    }
}
