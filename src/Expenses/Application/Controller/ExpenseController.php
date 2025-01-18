<?php

declare(strict_types=1);

namespace App\Expenses\Application\Controller;

use App\Expenses\Application\CreateExpenseCommand;
use App\Expenses\Application\Request\DTO\CreateExpenseRequestDTO;
use App\Expenses\Application\Response\Compiler\ExpenseItemCompiler;
use App\Expenses\Application\Response\Compiler\ExpensesSummaryCompiler;
use App\Expenses\Application\UpdateExpenseCommand;
use App\Expenses\Domain\ExpenseRepositoryInterface;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class ExpenseController extends AbstractController
{
    public function __construct(
        public MessageBusInterface $messageBus,
        public ExpenseRepositoryInterface $expenseRepository,
        public ExpenseItemCompiler $expenseItemCompiler,
        public ExpensesSummaryCompiler $expenseSummaryCompiler,
    ) {
    }

    #[Route('/expenses/{categoryId}/create', name: 'app_expenses_expense', requirements: ['categoryId' => '\d+'], methods: ['POST'])]
    public function create(int $categoryId, #[MapRequestPayload] CreateExpenseRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $this->messageBus->dispatch(new CreateExpenseCommand($dto->name, $dto->sum, $categoryId, $user));
        return $this->json(['success' => true]);
    }

    #[Route('/expenses/expense/{id}', name: 'app_expenses_expense_getbyid', requirements: ['id' => '\d+'])]
    public function getById(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $expense = $this->expenseRepository->getByIdAndUser($id, $user);
        return $this->json($this->expenseItemCompiler->compile($expense));
    }

    #[Route('/expenses/expense/edit/{id}', name: 'app_expenses_expense_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] CreateExpenseRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $expense = $this->expenseRepository->getByIdAndUser($id, $user);
        if (! $expense) {
            throw $this->createNotFoundException('No expense found for id ' . $id);
        }

        $this->messageBus->dispatch(new UpdateExpenseCommand($expense, $dto->name, $dto->sum));

        return $this->json(['success' => true]);
    }

    #[Route('/expenses/expense/delete/{id}', name: 'app_expenses_expense_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $expense = $this->expenseRepository->getByIdAndUser($id, $user);
        if (! $expense) {
            throw $this->createNotFoundException('No expense found for id ' . $id);
        }
        $this->expenseRepository->remove($expense);

        return $this->json(['success' => true]);
    }

    #[Route('/expenses/summary', name: 'app_expenses_summary')]
    public function summary(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->json(['summary' => $this->expenseSummaryCompiler->compile($user)]);
    }
}
