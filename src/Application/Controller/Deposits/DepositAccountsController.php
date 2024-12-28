<?php

declare(strict_types=1);

namespace App\Application\Controller\Deposits;

use App\Application\Request\DTO\Deposits\CreateAccountRequestDTO;
use App\Application\Response\Compiler\Deposits\DepositAccountFormDataCompiler;
use App\Application\Response\Compiler\Deposits\DepositAccountListItemsCompiler;
use App\Domain\Deposits\DepositAccount;
use App\Domain\Deposits\DepositAccountRepositoryInterface;
use App\Domain\Shared\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class DepositAccountsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DepositAccountRepositoryInterface $accountRepository,
        private readonly DepositAccountListItemsCompiler $depositAccountListItemsCompiler,
        private readonly DepositAccountFormDataCompiler $depositAccountFormDataCompiler,
    ) {
    }

    #[Route('/deposits/accounts', name: 'app_deposit_accounts', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $accounts = $this->accountRepository->getForUser($user);
        return $this->json(['items' => $this->depositAccountListItemsCompiler->compile($accounts)]);
    }

    #[Route('/deposits/accounts/create', name: 'app_deposit_accounts_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, #[MapRequestPayload] CreateAccountRequestDTO $dto): JsonResponse
    {
        $account = new DepositAccount($dto->name, $user);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/deposits/accounts/get-form/{id}', name: 'app_deposit_accounts_get_form', methods: ['GET'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $account = $this->accountRepository->getByIdAndUser($id, $user);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $id);
        }
        return $this->json($this->depositAccountFormDataCompiler->compile($account));
    }

    #[Route('/deposits/accounts/update/{id}', name: 'app_deposit_accounts_update', methods: ['POST'])]
    public function update(int $id, #[MapRequestPayload] CreateAccountRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $account = $this->accountRepository->getByIdAndUser($id, $user);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $id);
        }

        $account->setName($dto->name);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/deposits/accounts/delete/{id}', name: 'app_deposit_accounts_delete', methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $account = $this->accountRepository->getByIdAndUser($id, $user);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $id);
        }

        $this->entityManager->remove($account);
        $this->entityManager->flush();
        return $this->json(['success' => true]);
    }
}
