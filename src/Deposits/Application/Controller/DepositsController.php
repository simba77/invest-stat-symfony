<?php

declare(strict_types=1);

namespace App\Deposits\Application\Controller;

use App\Deposits\Application\CreateDepositCommand;
use App\Deposits\Application\Request\DTO\CreateDepositRequestDTO;
use App\Deposits\Application\Request\DTO\UpdateDepositRequestDTO;
use App\Deposits\Application\Response\Compiler\DepositFormDataCompiler;
use App\Deposits\Application\UseCases\GetDepositsPageUseCase;
use App\Deposits\Application\UpdateDepositCommand;
use App\Deposits\Domain\DepositAccountRepositoryInterface;
use App\Deposits\Domain\DepositRepositoryInterface;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class DepositsController extends AbstractController
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepository,
        private readonly GetDepositsPageUseCase $getDepositsPageUseCase,
        private readonly DepositAccountRepositoryInterface $depositAccountRepository,
        private readonly DepositFormDataCompiler $depositFormDataCompiler,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/deposits', name: 'app_deposits_index', methods: ['GET'])]
    public function index(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (! $user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = $request->query->getInt('perPage', PageRequestFactory::DEFAULT_PER_PAGE);

        return $this->json($this->getDepositsPageUseCase->execute($user, $page, $perPage));
    }

    #[Route('/deposits/create', name: 'app_deposits_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, #[MapRequestPayload] CreateDepositRequestDTO $dto): JsonResponse
    {
        $account = $this->depositAccountRepository->getByIdAndUser($dto->accountId, $user);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $dto->accountId);
        }

        $this->messageBus->dispatch(
            new CreateDepositCommand(
                amount:  $dto->sum,
                type:    $dto->type,
                date:    $dto->date,
                account: $account,
                user:    $user
            )
        );

        return $this->json(['success' => true]);
    }

    #[Route('/deposits/get-form/{id}', name: 'app_deposits_get_form', methods: ['GET'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $deposit = $this->depositRepository->getDepositByIdAndUserId($id, (int) $user?->getId());
        if (! $deposit) {
            throw $this->createNotFoundException('No deposits found for id ' . $id);
        }
        return $this->json($this->depositFormDataCompiler->compile($deposit));
    }

    #[Route('/deposits/update/{id}', name: 'app_deposits_update', methods: ['POST'])]
    public function update(int $id, #[MapRequestPayload] UpdateDepositRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $deposit = $this->depositRepository->getDepositByIdAndUserId($id, (int) $user?->getId());
        if (! $deposit) {
            throw $this->createNotFoundException('No deposits found for id ' . $id);
        }

        $account = $this->depositAccountRepository->getByIdAndUser($dto->accountId, $user);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $dto->accountId);
        }

        $this->messageBus->dispatch(
            new UpdateDepositCommand(
                deposit: $deposit,
                amount:  $dto->sum,
                type:    $dto->type,
                date:    $dto->date,
                account: $account,
            )
        );

        return $this->json(['success' => true]);
    }

    #[Route('/deposits/delete/{id}', name: 'app_deposits_delete', methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $deposit = $this->depositRepository->getDepositByIdAndUserId($id, (int) $user?->getId());
        if (! $deposit) {
            throw $this->createNotFoundException('No deposits found for id ' . $id);
        }

        $this->depositRepository->remove($deposit);
        return $this->json(['success' => true]);
    }
}
