<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Application\Request\DTO\Operations\CreateDividendRequestDTO;
use App\Investments\Application\Request\DTO\Operations\UpdateDividendRequestDTO;
use App\Investments\Application\UseCases\GetDividendsPageUseCase;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;
use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\DividendRepositoryInterface;
use App\Investments\Domain\Tax\TaxCalculatorInterface;
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
class DividendsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GetDividendsPageUseCase $getDividendsPageUseCase,
        private readonly TaxCalculatorInterface $taxCalculator,
        private readonly SyncedAccountGuard $syncedAccountGuard,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly ShareRepositoryInterface $shareRepository,
        private readonly ManualJournal $journal,
        private readonly DividendRepositoryInterface $dividendRepository,
    ) {
    }

    #[Route('/dividends', name: 'app_dividends_index')]
    public function index(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (! $user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = $request->query->getInt('perPage', PageRequestFactory::DEFAULT_PER_PAGE);

        return $this->json($this->getDividendsPageUseCase->execute($user, $page, $perPage));
    }

    #[Route('/dividends/create', name: 'app_dividends_create', requirements: ['categoryId' => '\d+'], methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateDividendRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        if (! $user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $account = $this->account($dto->accountId, $user);
        $this->syncedAccountGuard->assertManual($account);
        $tax = $this->taxCalculator->calculateFromNet($dto->amount, $user->getTaxProfile());

        $dividend = new Dividend(
            account:     $account,
            ticker:      $dto->ticker,
            stockMarket: $dto->stockMarket,
            amount:      $dto->amount,
            tax:         $tax->tax,
            date:        new \DateTimeImmutable($dto->date),
        );
        $dividend->setShare($this->shareRepository->findByTickerAndStockMarket($dto->ticker, $dto->stockMarket));

        $this->journal->changeRecords(function () use ($dividend): void {
            $this->em->persist($dividend);
            $this->em->flush();
        }, $account);

        return $this->json(['success' => true]);
    }

    #[Route('/dividends/get-form/{id}', name: 'app_dividends_getform', requirements: ['id' => '\d+'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $form = [];
        if ($id > 0) {
            $dividend = ($user !== null ? $this->dividendRepository->findByIdAndUser($id, $user) : null);
            if (! $dividend) {
                throw $this->createNotFoundException('No dividend found for id ' . $id);
            }
            $form = [
                'id'          => $dividend->getId(),
                'amount'      => $dividend->getAmount(),
                'date'        => $dividend->getDate()->format('Y-m-d'),
                'accountId'   => $dividend->getAccount()->getId(),
                'ticker'      => $dividend->getTicker(),
                'stockMarket' => $dividend->getStockMarket(),
            ];
        }

        return $this->json($form);
    }

    #[Route('/dividends/update/{id}', name: 'app_dividends_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] UpdateDividendRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        if (! $user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $dividend = $this->dividendRepository->findByIdAndUser($id, $user);
        if (! $dividend) {
            throw $this->createNotFoundException('No dividend found for id ' . $id);
        }

        $tax = $this->taxCalculator->calculateFromNet($dto->amount, $user->getTaxProfile());
        $account = $this->account($dto->accountId, $user);
        $this->syncedAccountGuard->assertManual($dividend->getAccount(), $account);
        $this->journal->changeRecords(function () use ($dividend, $dto, $tax, $account): void {
            $dividend->setDate(new \DateTimeImmutable($dto->date));
            $dividend->setAmount($dto->amount);
            $dividend->setTax($tax->tax);
            $dividend->setTicker($dto->ticker);
            $dividend->setStockMarket($dto->stockMarket);
            $dividend->setShare($this->shareRepository->findByTickerAndStockMarket($dto->ticker, $dto->stockMarket));
            $dividend->setAccount($account);
            $this->em->flush();
        }, $dividend->getAccount(), $account);

        return $this->json(['success' => true]);
    }

    #[Route('/dividends/delete/{id}', name: 'app_dividends_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $dividend = ($user !== null ? $this->dividendRepository->findByIdAndUser($id, $user) : null);
        if (! $dividend) {
            throw $this->createNotFoundException('No dividend found for id ' . $id);
        }
        $this->syncedAccountGuard->assertManual($dividend->getAccount());
        $this->journal->changeRecords(function () use ($dividend): void {
            $this->em->remove($dividend);
            $this->em->flush();
        }, $dividend->getAccount());

        return $this->json(['success' => true]);
    }

    private function account(int $id, User $user): Account
    {
        return $this->accountRepository->getByIdAndUser($id, $user)
            ?? throw $this->createNotFoundException('No account found for id ' . $id);
    }
}
