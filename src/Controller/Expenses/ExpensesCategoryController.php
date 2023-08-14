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
        protected EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/expenses', name: 'app_expenses_category')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->json($this->expensesCategoryService->getCategories($user));
    }

    #[Route('/expenses/category/create', name: 'app_expensescategory_create')]
    public function create(
        #[MapRequestPayload] CreateCategoryRequestDTO $dto,
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if ($request->isXmlHttpRequest()) {
            $expensesCategory = new ExpensesCategory();
            $expensesCategory->setName($dto->name);
            $expensesCategory->setUserId($user->getId());
            $expensesCategory->setCreatedBy($user->getId());
            $expensesCategory->setUpdatedBy($user->getId());
            $expensesCategory->setCreatedAt(new \DateTimeImmutable());
            $expensesCategory->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($expensesCategory);
            $this->entityManager->flush();
        }

        return $this->json(['success' => true]);
    }
}
