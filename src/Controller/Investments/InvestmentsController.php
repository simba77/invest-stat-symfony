<?php

declare(strict_types=1);

namespace App\Controller\Investments;

use App\Entity\Account;
use App\Entity\Expense;
use App\Entity\Investment;
use App\Entity\User;
use App\Request\DTO\Expenses\CreateExpenseRequestDTO;
use App\Request\DTO\Investments\InvestmentRequestDTO;
use App\Services\AccountService;
use App\Services\InvestmentsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class InvestmentsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InvestmentsService $investmentsService,
        private readonly AccountService $accountService
    ) {
    }

    #[Route('/investments', name: 'app_investments_investments_index')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $items = $this->investmentsService->getInvestmentsForUser($user);
        return $this->json(['items' => $items]);
    }

    #[Route('/investments/create', name: 'app_investments_investments_create', requirements: ['categoryId' => '\d+'], methods: ['POST'])]
    public function create(#[MapRequestPayload] InvestmentRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $account = $this->em->getRepository(Account::class)->find($dto->account);
        $inv = new Investment($dto->sum, new \DateTimeImmutable($dto->date), $account, $user->getId());
        $this->em->persist($inv);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/investments/get-form/{id}', name: 'app_investments_investments_getbyid', requirements: ['id' => '\d+'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $form = [];
        $accounts = $this->accountService->getAccountsForUser($user);
        return $this->json(['form' => $form, 'accounts' => $accounts]);
    }

    #[Route('/investments/edit/{id}', name: 'app_investments_investments_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] CreateExpenseRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $expense = $this->em->getRepository(Expense::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
        if (! $expense) {
            throw $this->createNotFoundException('No expense found for id ' . $id);
        }

        $expense->setName($dto->name);
        $expense->setSum($dto->sum);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/investments/delete/{id}', name: 'app_investments_investments_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $expense = $this->em->getRepository(Expense::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
        if (! $expense) {
            throw $this->createNotFoundException('No expense found for id ' . $id);
        }
        $this->em->remove($expense);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

}
