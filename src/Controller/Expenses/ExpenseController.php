<?php

declare(strict_types=1);

namespace App\Controller\Expenses;

use App\Entity\Expense;
use App\Entity\User;
use App\Request\DTO\Expenses\CreateExpenseRequestDTO;
use App\Services\ExpenseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ExpenseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ExpenseService $expenseService
    ) {
    }

    #[Route('/expenses/{categoryId}/create', name: 'app_expenses_expense', requirements: ['categoryId' => '\d+'], methods: ['POST'])]
    public function create(int $categoryId, #[MapRequestPayload] CreateExpenseRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $expense = new Expense($dto->name, $dto->sum, $categoryId, $user->getId());
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/expenses/expense/{id}', name: 'app_expenses_expense_getbyid', requirements: ['id' => '\d+'])]
    public function getById(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $expense = $this->expenseService->getByIdAndUser($id, $user);
        return $this->json($expense);
    }

    #[Route('/expenses/edit/{id}', name: 'app_expenses_expense_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] CreateExpenseRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $expense = $this->entityManager->getRepository(Expense::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
        if (! $expense) {
            throw $this->createNotFoundException('No expense found for id ' . $id);
        }

        $expense->setName($dto->name);
        $expense->setSum($dto->sum);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }
}
