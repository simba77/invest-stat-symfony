<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

use GO\Scheduler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'schedule:run',
    description: 'Run the Task Scheduler',
)]
class ScheduleCommand extends Command
{
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rootDir = dirname(__DIR__, 4);

        try {
            $scheduler = new Scheduler();

            // Tasks
            $scheduler->php($rootDir . '/bin/console accounts:save-stat')->everyMinute(30);
            $scheduler->php($rootDir . '/bin/console currency:get-rates')->everyMinute();
            $scheduler->php($rootDir . '/bin/console securities:get-moex-bonds')->everyMinute(15);
            $scheduler->php($rootDir . '/bin/console securities:get-moex-futures')->everyMinute(15);
            $scheduler->php($rootDir . '/bin/console securities:get-moex-shares')->everyMinute(15);
            // $scheduler->php($rootDir . '/bin/console securities:get-spb-shares')->everyMinute(5);
            $scheduler->php($rootDir . '/bin/console securities:update-instruments')->daily(19);
            $scheduler->php($rootDir . '/bin/console securities:get-market-data')->everyMinute(2);

            // Set daily profit to 0. (Set current prices as prev)
            $scheduler->php($rootDir . '/bin/console securities:update-prev-prices')->daily(3);

            $list = $scheduler->run();
            foreach ($list as $item) {
                $io->success('Task ' . $item->getId() . ' is completed');
            }

            if (empty($list)) {
                $io->success('There are no tasks to complete');
            }
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());
        }

        return Command::SUCCESS;
    }
}
