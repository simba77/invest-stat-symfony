<?php

declare(strict_types=1);

namespace App\Controller\Accounts;

use App\Entity\Account;
use App\Entity\User;
use App\Request\DTO\Accounts\CreateAccountRequestDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AccountsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    #[Route('/accounts', name: 'app_accounts_accounts_index')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->json([]);
    }

    #[Route('/accounts/create', name: 'app_accounts_accounts_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateAccountRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $account = new Account(
            userId:            $user->getId(),
            name:              $dto->name,
            balance:           $dto->balance,
            usdBalance:        $dto->usdBalance,
            commission:        $dto->commission,
            futuresCommission: $dto->futuresCommission,
            sort:              $dto->sort
        );

        $this->em->persist($account);
        $this->em->flush();

        return $this->json(['success' => true]);
    }
}
