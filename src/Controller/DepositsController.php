<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\DepositAccount;
use App\Entity\User;
use App\Request\DTO\Deposits\CreateAccountRequestDTO;
use App\Services\DepositsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class DepositsController extends AbstractController
{
    public function __construct(
        private readonly DepositsService $depositsService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/deposits', name: 'app_deposits_index', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $deposits = $this->depositsService->getAllDepositsForUser($user);
        return $this->json(['items' => $deposits]);
    }

    #[Route('/deposits/accounts', name: 'app_deposits_accounts', methods: ['GET'])]
    public function accounts(#[CurrentUser] ?User $user): JsonResponse
    {
        $accounts = $this->depositsService->getDepositAccountsForUser($user);
        return $this->json(['items' => $accounts]);
    }

    #[Route('/deposits/accounts/create/', name: 'app_deposits_create_account', methods: ['POST'])]
    public function createAccount(#[CurrentUser] ?User $user, #[MapRequestPayload] CreateAccountRequestDTO $dto): JsonResponse
    {
        $account = new DepositAccount($dto->name, $user);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
        return $this->json(['success' => true]);
    }
}
