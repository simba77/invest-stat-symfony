<?php

declare(strict_types=1);

namespace App\Expenses\Application\Controller;

use App\Expenses\Application\CreateExpenseCommand;
use App\Expenses\Application\Request\DTO\CreateExpenseRequestDTO;
use App\Expenses\Domain\Expense;
use App\Expenses\Domain\ExpenseService;
use App\Shared\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $em,
        private readonly ExpenseService $expenseService,
        public MessageBusInterface $messageBus,
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
        $expense = $this->expenseService->getByIdAndUser($id, $user);
        return $this->json($expense);
    }

    #[Route('/expenses/expense/edit/{id}', name: 'app_expenses_expense_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] CreateExpenseRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $expense = $this->em->getRepository(Expense::class)->findOneBy(['id' => $id, 'userId' => $user->getId()]);
        if (! $expense) {
            throw $this->createNotFoundException('No expense found for id ' . $id);
        }

        $expense->setName($dto->name);
        $expense->setSum($dto->sum);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/expenses/expense/delete/{id}', name: 'app_expenses_expense_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $expense = $this->em->getRepository(Expense::class)->findOneBy(['id' => $id, 'userId' => $user->getId()]);
        if (! $expense) {
            throw $this->createNotFoundException('No expense found for id ' . $id);
        }
        $this->em->remove($expense);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/expenses/summary', name: 'app_expenses_summary')]
    public function summary(#[CurrentUser] ?User $user): JsonResponse
    {
        $expenses = $this->em->getRepository(Expense::class)->getSumForUser($user->getId());
        return $this->json(
            [
                'summary' => [
                    [
                        'name'  => 'Salary',
                        'total' => $user->getSalary(),
                    ],
                    [
                        'name'  => 'All Expenses',
                        'total' => $expenses,
                    ],
                    [
                        'name'     => 'Salary - Expenses',
                        'helpText' => 'Free Money for Investments',
                        'total'    => bcsub($user->getSalary(), $expenses, 2),
                    ],
                ],
            ]
        );
    }
}
