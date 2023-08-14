<?php

declare(strict_types=1);

namespace App\Controller\Expenses;

use App\Entity\Expense;
use App\Entity\User;
use App\Request\DTO\Expenses\CreateExpenseRequestDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ExpenseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
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
}
