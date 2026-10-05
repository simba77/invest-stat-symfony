<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\Accounts\CloseAccountCommand;
use App\Investments\Application\Accounts\CreateAccountCommand;
use App\Investments\Application\Accounts\DeleteAccountCommand;
use App\Investments\Application\Accounts\UpdateAccountCommand;
use App\Investments\Application\Journal\CancelOperationCommand;
use App\Investments\Application\Journal\CorrectSaleCommand;
use App\Investments\Application\Request\DTO\CreateAccountRequestDTO;
use App\Investments\Application\Request\DTO\Operations\CorrectSaleRequestDTO;
use App\Investments\Application\Response\Compiler\AccountEditFormCompiler;
use App\Investments\Application\Response\Compiler\AccountsListCompiler;
use App\Investments\Application\UseCases\Journal\GetAccountOperationsPageUseCase;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Domain\Bus\SyncCommandBusInterface;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly GetAccountOperationsPageUseCase $getAccountOperationsPageUseCase,
    ) {
    }

    #[Route('/accounts', name: 'app_accounts_accounts_index')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $accounts = $this->accountRepository->findByUserWithDeposits($user->getId());
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
                blockedBalance:    $dto->blockedBalance,
                blockedUsdBalance: $dto->blockedUsdBalance,
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
                blockedBalance:    $dto->blockedBalance,
                blockedUsdBalance: $dto->blockedUsdBalance,
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

    #[Route('/accounts/{id}/operations', name: 'app_accounts_accounts_operations', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function operations(int $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = $request->query->getInt('perPage', PageRequestFactory::DEFAULT_PER_PAGE);

        return $this->json($this->getAccountOperationsPageUseCase->execute($id, $user, $page, $perPage));
    }

    #[Route('/accounts/{id}/operations/{operationId}/cancel', name: 'app_accounts_accounts_operations_cancel', requirements: ['id' => '\d+', 'operationId' => '\d+'], methods: ['POST'])]
    public function cancelOperation(int $id, int $operationId, #[CurrentUser] ?User $user): JsonResponse
    {
        $this->commandBus->dispatch(new CancelOperationCommand(accountId: $id, operationId: $operationId, user: $user));

        return $this->json(['success' => true]);
    }

    #[Route('/accounts/{id}/operations/{operationId}/edit', name: 'app_accounts_accounts_operations_edit', requirements: ['id' => '\d+', 'operationId' => '\d+'], methods: ['POST'])]
    public function correctSale(int $id, int $operationId, #[MapRequestPayload] CorrectSaleRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $this->commandBus->dispatch(new CorrectSaleCommand(
            accountId:   $id,
            operationId: $operationId,
            user:        $user,
            price:       $dto->price,
            executedAt:  new \DateTimeImmutable($dto->executedAt),
        ));

        return $this->json(['success' => true]);
    }

    #[Route('/accounts/close/{id}', name: 'app_accounts_accounts_close', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function close(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $this->commandBus->dispatch(new CloseAccountCommand(accountId: $id, user: $user));

        return $this->json(['success' => true]);
    }

    #[Route('/accounts/reopen/{id}', name: 'app_accounts_accounts_reopen', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reopen(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $this->commandBus->dispatch(new CloseAccountCommand(accountId: $id, user: $user, close: false));

        return $this->json(['success' => true]);
    }

    #[Route('/accounts/delete/{id}', name: 'app_accounts_accounts_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
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
