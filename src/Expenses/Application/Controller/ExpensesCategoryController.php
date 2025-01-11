<?php

declare(strict_types=1);

namespace App\Expenses\Application\Controller;

use App\Expenses\Application\Request\DTO\CreateCategoryRequestDTO;
use App\Expenses\Application\Response\DTO\ExpenseCategoryDTO;
use App\Expenses\Domain\ExpensesCategory;
use App\Expenses\Domain\ExpensesCategoryService;
use App\Shared\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
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

    #[Route('/expenses/category/delete/{id}', name: 'app_expenses_category_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $category = $this->em->getRepository(ExpensesCategory::class)->findOneBy(['id' => $id, 'userId' => $user->getId()]);
        if (! $category) {
            throw $this->createNotFoundException('No category found for id ' . $id);
        }

        // Remove related expenses
        $expenses = $category->getExpenses();
        foreach ($expenses as $expense) {
            $this->em->remove($expense);
        }

        $this->em->remove($category);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/expenses/category/{id}', name: 'app_expenses_category_get_by_id', requirements: ['id' => '\d+'])]
    public function getById(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $category = $this->em->getRepository(ExpensesCategory::class)->findOneBy(['id' => $id, 'userId' => $user->getId()]);
        return $this->json(new ExpenseCategoryDTO($category->getId(), $category->getName(), []));
    }

    #[Route('/expenses/category/edit/{id}', name: 'app_expenses_category_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] CreateCategoryRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $category = $this->em->getRepository(ExpensesCategory::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
        if (! $category) {
            throw $this->createNotFoundException('No category found for id ' . $id);
        }

        $category->setName($dto->name);
        $this->em->flush();

        return $this->json(['success' => true]);
    }
}
