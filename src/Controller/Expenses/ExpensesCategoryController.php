<?php

declare(strict_types=1);

namespace App\Controller\Expenses;

use App\Entity\ExpensesCategory;
use App\Entity\User;
use App\Request\DTO\Expenses\CreateCategoryRequestDTO;
use App\Services\ExpensesCategoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ExpensesCategoryController extends AbstractController
{
    public function __construct(
        private readonly ExpensesCategoryService $expensesCategoryService,
        protected EntityManagerInterface $em,
    ) {
    }

    #[Route('/expenses', name: 'app_expenses_category')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->json($this->expensesCategoryService->getCategories($user));
    }

    #[Route('/expenses/category/create', name: 'app_expenses_category_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateCategoryRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $expensesCategory = new ExpensesCategory($dto->name, $user->getId());
        $this->em->persist($expensesCategory);
        $this->em->flush();

        return $this->json(['success' => true]);
    }
}
