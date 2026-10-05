<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Application\Request\DTO\Operations\InvestmentRequestDTO;
use App\Investments\Application\Response\Compiler\AccountsSimpleListCompiler;
use App\Investments\Application\UseCases\GetInvestmentsPageUseCase;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Operations\Investment;
use App\Investments\Domain\Operations\InvestmentRepositoryInterface;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly GetInvestmentsPageUseCase $getInvestmentsPageUseCase,
        protected readonly AccountRepositoryInterface $accountRepository,
        protected readonly AccountsSimpleListCompiler $accountsSimpleListCompiler,
        private readonly SyncedAccountGuard $syncedAccountGuard,
        private readonly ManualJournal $journal,
        private readonly InvestmentRepositoryInterface $investmentRepository,
    ) {
    }

    #[Route('/investments', name: 'app_investments_investments_index')]
    public function index(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (! $user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = $request->query->getInt('perPage', PageRequestFactory::DEFAULT_PER_PAGE);

        return $this->json($this->getInvestmentsPageUseCase->execute($user, $page, $perPage));
    }

    #[Route('/investments/create', name: 'app_investments_investments_create', requirements: ['categoryId' => '\d+'], methods: ['POST'])]
    public function create(#[MapRequestPayload] InvestmentRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        if (! $user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $account = $this->account($dto->account, $user);
        $this->syncedAccountGuard->assertManual($account);
        $investment = new Investment($dto->sum, new \DateTimeImmutable($dto->date), $account);
        $this->journal->changeRecords(function () use ($investment): void {
            $this->em->persist($investment);
            $this->em->flush();
        }, $account);

        return $this->json(['success' => true]);
    }

    #[Route('/investments/get-form/{id}', name: 'app_investments_investments_getbyid', requirements: ['id' => '\d+'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $form = [];
        if ($id > 0) {
            $investment = ($user !== null ? $this->investmentRepository->findByIdAndUser($id, $user) : null);
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
        if (! $user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $investment = $this->investmentRepository->findByIdAndUser($id, $user);
        if (! $investment) {
            throw $this->createNotFoundException('No investment found for id ' . $id);
        }
        $account = $this->account($dto->account, $user);
        $this->syncedAccountGuard->assertManual($investment->getAccount(), $account);
        $this->journal->changeRecords(function () use ($investment, $dto, $account): void {
            $investment->setDate(new \DateTimeImmutable($dto->date));
            $investment->setSum($dto->sum);
            $investment->setAccount($account);
            $this->em->flush();
        }, $investment->getAccount(), $account);

        return $this->json(['success' => true]);
    }

    #[Route('/investments/delete/{id}', name: 'app_investments_investments_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $investment = ($user !== null ? $this->investmentRepository->findByIdAndUser($id, $user) : null);
        if (! $investment) {
            throw $this->createNotFoundException('No investment found for id ' . $id);
        }
        $this->syncedAccountGuard->assertManual($investment->getAccount());
        $this->journal->changeRecords(function () use ($investment): void {
            $this->em->remove($investment);
            $this->em->flush();
        }, $investment->getAccount());

        return $this->json(['success' => true]);
    }

    private function account(int $id, User $user): Account
    {
        return $this->accountRepository->getByIdAndUser($id, $user)
            ?? throw $this->createNotFoundException('No account found for id ' . $id);
    }
}
