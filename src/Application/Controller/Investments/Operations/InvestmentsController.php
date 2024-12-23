<?php

declare(strict_types=1);

namespace App\Application\Controller\Investments\Operations;

use App\Application\Request\DTO\Investments\Operations\InvestmentRequestDTO;
use App\Domain\Investments\Accounts\Account;
use App\Domain\Investments\Accounts\AccountService;
use App\Domain\Investments\Operations\Investment;
use App\Domain\Investments\Operations\InvestmentsService;
use App\Domain\Shared\User;
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

        $accounts = $this->accountService->getSimpleListOfAccountsForUser($user);
        return $this->json(['form' => $form, 'accounts' => $accounts]);
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
