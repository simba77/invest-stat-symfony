<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Deposits\Infrastructure\Persistence\Repository\DepositAccountRepository;
use App\Deposits\Infrastructure\Persistence\Repository\DepositRepository;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->set(\App\Deposits\Domain\DepositRepositoryInterface::class, DepositRepository::class)
        ->set(\App\Deposits\Domain\DepositAccountRepositoryInterface::class, DepositAccountRepository::class);
};
