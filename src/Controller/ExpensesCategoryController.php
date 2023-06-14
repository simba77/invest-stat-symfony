<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ExpensesCategory;
use App\Entity\User;
use App\Services\ExpensesCategoryService;
use App\Validation\ValidationErrorsCollector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    /**
     * @throws ExceptionInterface
     */
    #[Route('/expenses/category/create', name: 'app_expensescategory_create')]
    public function create(ValidatorInterface $validator, Request $request, DenormalizerInterface $normalizer, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($request->isXmlHttpRequest()) {
            $content = $request->toArray();

            $valid = $validator->validate(
                $content,
                new Assert\Collection(
                    [
                        'name' => new Assert\Length(min: 3, max: 200),
                    ]
                )
            );

            if ($valid->count() > 0) {
                $validationErrors = (new ValidationErrorsCollector($valid))->getErrors();
                return $this->json(['errors' => $validationErrors], 422);
            }

            $expensesCategory = $normalizer->denormalize($content, ExpensesCategory::class, null, ['groups' => ['expensesForm']]);
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
