<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\Operations\Deals\PortfolioQuery;
use App\Shared\Domain\Bus\QueryBusInterface;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/portfolio', name: 'app_full_portfolio')]
class PortfolioController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(#[CurrentUser] ?User $user): Response
    {
        $portfolio = $this->queryBus->ask(new PortfolioQuery($user->getId()));
        return $this->json($portfolio);
    }
}
