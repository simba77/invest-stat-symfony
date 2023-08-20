<?php

declare(strict_types=1);

namespace App\Controller\Accounts;

use App\Entity\Account;
use App\Entity\User;
use App\Request\DTO\Accounts\CreateAccountRequestDTO;
use App\Services\AccountService;
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
        private readonly EntityManagerInterface $em,
        private readonly AccountService $accountService
    ) {
    }

    #[Route('/accounts', name: 'app_accounts_accounts_index')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $list = $this->accountService->getAccountsListForUser($user);
        return $this->json($list);
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

    #[Route('/accounts/update/{id}', name: 'app_accounts_accounts_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(int $id, #[MapRequestPayload] CreateAccountRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $account = $this->em->getRepository(Account::class)->findOneBy(['id' => $id, 'userId' => $user->getId()]);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $id);
        }

        $account->setName($dto->name);
        $account->setBalance($dto->balance);
        $account->setUsdBalance($dto->usdBalance);
        $account->setCommission($dto->commission);
        $account->setFuturesCommission($dto->futuresCommission);
        $account->setSort($dto->sort);
        $this->em->persist($account);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/accounts/get-form/{id}', name: 'app_accounts_accounts_getform', requirements: ['id' => '\d+'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $form = $this->accountService->getEditForm($id, $user->getId() ?? 0);
        if (! $form) {
            throw $this->createNotFoundException('No accounts found for id ' . $id);
        }
        return $this->json($form);
    }

    #[Route('/accounts/delete/{id}', name: 'app_accounts_accounts_delete', requirements: ['id' => '\d+'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $account = $this->em->getRepository(Account::class)->findOneBy(['id' => $id, 'userId' => $user->getId()]);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $id);
        }

        $this->em->remove($account);
        $this->em->flush();

        return $this->json(['success' => true]);
    }
}
