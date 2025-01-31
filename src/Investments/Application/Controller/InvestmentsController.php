<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\Request\DTO\Operations\InvestmentRequestDTO;
use App\Investments\Application\Response\DTO\Compiler\AccountsSimpleListCompiler;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Operations\Investment;
use App\Investments\Domain\Operations\InvestmentsService;
use App\Shared\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class InvestmentsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InvestmentsService $investmentsService,
        protected readonly AccountRepositoryInterface $accountRepository,
        protected readonly AccountsSimpleListCompiler $accountsSimpleListCompiler,
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
        if ($id > 0) {
            $investment = $this->em->getRepository(Investment::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
            if (! $investment) {
                throw $this->createNotFoundException('No investment found for id ' . $id);
            }
            $form = [
                'id'      => $investment->getId(),
                'sum'     => $investment->getSum(),
                'date'    => $investment->getDate()->format('Y-m-d'),
                'account' => $investment->getAccount()->getId(),
            ];
        }

        $accounts = $this->accountRepository->findByUser($user);
        return $this->json(
            [
                'form'     => $form,
                'accounts' => $this->accountsSimpleListCompiler->compile($accounts),
            ]
        );
    }

    #[Route('/investments/edit/{id}', name: 'app_investments_investments_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] InvestmentRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $investment = $this->em->getRepository(Investment::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
        if (! $investment) {
            throw $this->createNotFoundException('No investment found for id ' . $id);
        }
        $account = $this->em->getRepository(Account::class)->find($dto->account);
        $investment->setDate(new \DateTimeImmutable($dto->date));
        $investment->setSum($dto->sum);
        $investment->setAccount($account);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/investments/delete/{id}', name: 'app_investments_investments_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $investment = $this->em->getRepository(Investment::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
        if (! $investment) {
            throw $this->createNotFoundException('No investment found for id ' . $id);
        }
        $this->em->remove($investment);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

}
