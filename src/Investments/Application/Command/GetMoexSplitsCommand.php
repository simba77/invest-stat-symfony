<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Application\Instruments\ShareSplitsUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'securities:get-moex-splits',
    description: 'Get share splits from MOEX',
)]
class GetMoexSplitsCommand extends Command
{
    public function __construct(
        private readonly ShareSplitsUpdater $shareSplitsUpdater,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $added = $this->shareSplitsUpdater->update();
        $io->success(sprintf('New splits: %d', $added));

        return Command::SUCCESS;
    }
}
