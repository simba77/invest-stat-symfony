<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\Accounts\CreateAccountCommand;
use App\Investments\Application\Accounts\DeleteAccountCommand;
use App\Investments\Application\Accounts\UpdateAccountCommand;
use App\Investments\Application\Request\DTO\CreateAccountRequestDTO;
use App\Investments\Application\Response\Compiler\AccountEditFormCompiler;
use App\Investments\Application\Response\Compiler\AccountsListCompiler;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Shared\Domain\Bus\SyncCommandBusInterface;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class AccountsController extends AbstractController
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly AccountsListCompiler $accountsListCompiler,
        private readonly SyncCommandBusInterface $commandBus,
        private readonly AccountEditFormCompiler $accountEditFormCompiler,
    ) {
    }

    #[Route('/accounts', name: 'app_accounts_accounts_index')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $accounts = $this->accountRepository->findByUserWithDeposits($user);
        return $this->json($this->accountsListCompiler->compile($accounts));
    }

    #[Route('/accounts/create', name: 'app_accounts_accounts_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateAccountRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $this->commandBus->dispatch(
            new CreateAccountCommand(
                user:              $user,
                name:              $dto->name,
                balance:           $dto->balance,
                usdBalance:        $dto->usdBalance,
                commission:        $dto->commission,
                futuresCommission: $dto->futuresCommission,
                sort:              $dto->sort
            )
        );
        return $this->json(['success' => true]);
    }

    #[Route('/accounts/update/{id}', name: 'app_accounts_accounts_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(int $id, #[MapRequestPayload] CreateAccountRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $this->commandBus->dispatch(
            new UpdateAccountCommand(
                accountId:         $id,
                user:              $user,
                name:              $dto->name,
                balance:           $dto->balance,
                usdBalance:        $dto->usdBalance,
                commission:        $dto->commission,
                futuresCommission: $dto->futuresCommission,
                sort:              $dto->sort,
            )
        );

        return $this->json(['success' => true]);
    }

    #[Route('/accounts/get-form/{id}', name: 'app_accounts_accounts_getform', requirements: ['id' => '\d+'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $account = $this->accountRepository->getByIdAndUser($id, $user);
        if (! $account) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $id));
        }
        return $this->json($this->accountEditFormCompiler->compile($account));
    }

    #[Route('/accounts/delete/{id}', name: 'app_accounts_accounts_delete', requirements: ['id' => '\d+'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $this->commandBus->dispatch(
            new DeleteAccountCommand(
                accountId: $id,
                user:      $user
            )
        );
        return $this->json(['success' => true]);
    }
}
