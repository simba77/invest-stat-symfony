<?php

declare(strict_types=1);

namespace App\Expenses\Application\Controller;

use App\Expenses\Application\DeleteCategoryCommand;
use App\Expenses\Application\Request\DTO\CreateCategoryRequestDTO;
use App\Expenses\Application\Response\Compiler\CategoriesListCompiler;
use App\Expenses\Application\Response\DTO\ExpenseCategoryDTO;
use App\Expenses\Domain\ExpensesCategory;
use App\Expenses\Domain\ExpensesCategoryRepositoryInterface;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class ExpensesCategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoriesListCompiler $categoriesListCompiler,
        private readonly ExpensesCategoryRepositoryInterface $expensesCategoryRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/expenses', name: 'app_expenses_category')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $categories = $this->expensesCategoryRepository->getCategoriesForUser($user);
        return $this->json(['items' => $this->categoriesListCompiler->compile($categories)]);
    }

    #[Route('/expenses/category/create', name: 'app_expenses_category_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateCategoryRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $expensesCategory = new ExpensesCategory($dto->name, $user->getId());
        $this->expensesCategoryRepository->save($expensesCategory);

        return $this->json(['success' => true]);
    }

    #[Route('/expenses/category/delete/{id}', name: 'app_expenses_category_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $category = $this->expensesCategoryRepository->getByIdAndUser($id, $user);
        if (! $category) {
            throw $this->createNotFoundException('No category found for id ' . $id);
        }

        $this->messageBus->dispatch(new DeleteCategoryCommand($category));

        return $this->json(['success' => true]);
    }

    #[Route('/expenses/category/{id}', name: 'app_expenses_category_get_by_id', requirements: ['id' => '\d+'])]
    public function getById(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $category = $this->expensesCategoryRepository->getByIdAndUser($id, $user);
        return $this->json(new ExpenseCategoryDTO($category->getId(), $category->getName(), []));
    }

    #[Route('/expenses/category/edit/{id}', name: 'app_expenses_category_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] CreateCategoryRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $category = $this->expensesCategoryRepository->getByIdAndUser($id, $user);
        if (! $category) {
            throw $this->createNotFoundException('No category found for id ' . $id);
        }

        $category->setName($dto->name);
        $this->expensesCategoryRepository->save($category);

        return $this->json(['success' => true]);
    }
}
