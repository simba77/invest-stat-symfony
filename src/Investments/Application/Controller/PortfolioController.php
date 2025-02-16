<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\Operations\Deals\PortfolioCompilerData;
use App\Investments\Application\Response\Compiler\PortfolioCompiler;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class PortfolioController extends AbstractController
{
    public function __construct(
        private readonly DealRepositoryInterface $dealRepository,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly PortfolioCompiler $compiler,
    ) {
    }

    #[Route('/portfolio', name: 'app_full_portfolio')]
    public function __invoke(#[CurrentUser] ?User $user): Response
    {
        $compilerData = new PortfolioCompilerData(
            deals:    $this->dealRepository->findForUser($user),
            accounts: $this->accountRepository->findByUserWithDeposits($user)
        );

        return $this->json($this->compiler->compile($compilerData));
    }
}
