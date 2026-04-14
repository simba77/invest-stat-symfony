<?php

declare(strict_types=1);

namespace App\Deposits\Application\Controller;

use App\Deposits\Application\CreateDepositCommand;
use App\Deposits\Application\Request\DTO\CreateDepositRequestDTO;
use App\Deposits\Application\Request\DTO\UpdateDepositRequestDTO;
use App\Deposits\Application\Response\Compiler\DepositFormDataCompiler;
use App\Deposits\Application\UseCases\GetDepositsPageUseCase;
use App\Deposits\Application\UpdateDepositCommand;
use App\Deposits\Domain\DepositAccountRepositoryInterface;
use App\Deposits\Domain\DepositRepositoryInterface;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class DepositsController extends AbstractController
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepository,
        private readonly GetDepositsPageUseCase $getDepositsPageUseCase,
        private readonly DepositAccountRepositoryInterface $depositAccountRepository,
        private readonly DepositFormDataCompiler $depositFormDataCompiler,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/deposits', name: 'app_deposits_index', methods: ['GET'])]
    public function index(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (! $user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = $request->query->getInt('perPage', PageRequestFactory::DEFAULT_PER_PAGE);

        return $this->json($this->getDepositsPageUseCase->execute($user, $page, $perPage));
    }

    #[Route('/deposits/create', name: 'app_deposits_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, #[MapRequestPayload] CreateDepositRequestDTO $dto): JsonResponse
    {
        $account = $this->depositAccountRepository->getByIdAndUser($dto->accountId, $user);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $dto->accountId);
        }

        $this->messageBus->dispatch(
            new CreateDepositCommand(
                amount:  $dto->sum,
                type:    $dto->type,
                date:    $dto->date,
                account: $account,
                user:    $user
            )
        );

        return $this->json(['success' => true]);
    }

    #[Route('/deposits/get-form/{id}', name: 'app_deposits_get_form', methods: ['GET'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $deposit = $this->depositRepository->getDepositByIdAndUserId($id, (int) $user?->getId());
        if (! $deposit) {
            throw $this->createNotFoundException('No deposits found for id ' . $id);
        }
        return $this->json($this->depositFormDataCompiler->compile($deposit));
    }

    #[Route('/deposits/update/{id}', name: 'app_deposits_update', methods: ['POST'])]
    public function update(int $id, #[MapRequestPayload] UpdateDepositRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $deposit = $this->depositRepository->getDepositByIdAndUserId($id, (int) $user?->getId());
        if (! $deposit) {
            throw $this->createNotFoundException('No deposits found for id ' . $id);
        }

        $account = $this->depositAccountRepository->getByIdAndUser($dto->accountId, $user);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $dto->accountId);
        }

        $this->messageBus->dispatch(
            new UpdateDepositCommand(
                deposit: $deposit,
                amount:  $dto->sum,
                type:    $dto->type,
                date:    $dto->date,
                account: $account,
            )
        );

        return $this->json(['success' => true]);
    }

    #[Route('/deposits/stats', name: 'app_deposits_stats', methods: ['GET'])]
    public function stats(#[CurrentUser] ?User $user): JsonResponse
    {
        if (! $user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $monthlyStats = $this->depositRepository->getMonthlyStats((int) $user->getId());
        $accounts = $this->depositAccountRepository->getAccountStats($user);
        $transactionsByAccount = $this->depositRepository->getTransactionsByAccount((int) $user->getId());

        $totalBalance = '0';
        $totalProfit = '0';
        $totalGrossInvested = '0';
        $totalActiveDays = 0;

        $accountsData = array_map(function (array $account) use ($transactionsByAccount, &$totalBalance, &$totalProfit, &$totalGrossInvested, &$totalActiveDays) {
            $grossInvested = (string) ($account['gross_invested'] ?? '0');
            $profit = (string) ($account['profit'] ?? '0');
            $balance = (string) ($account['balance'] ?? '0');

            $txs = $transactionsByAccount[(int) $account['id']] ?? [];
            $activeDays = $this->calculateActiveDays($txs);

            $profitPercent = '0';
            $annualizedPercent = '0';
            if (bccomp($grossInvested, '0', 2) > 0) {
                $profitPercent = bcdiv(bcmul($profit, '100', 6), $grossInvested, 2);
                $annualizedPercent = bcdiv(bcmul($profitPercent, '365', 6), (string) $activeDays, 2);
            }

            $totalBalance = bcadd($totalBalance, $balance, 2);
            $totalProfit = bcadd($totalProfit, $profit, 2);
            $totalGrossInvested = bcadd($totalGrossInvested, $grossInvested, 2);
            $totalActiveDays += $activeDays;

            return [
                'id'                => $account['id'],
                'name'              => $account['name'],
                'balance'           => $balance,
                'profit'            => $profit,
                'grossInvested'     => $grossInvested,
                'profitPercent'     => $profitPercent,
                'annualizedPercent' => $annualizedPercent,
            ];
        }, $accounts);

        $summaryProfitPercent = '0';
        $summaryAnnualizedPercent = '0';
        if (bccomp($totalGrossInvested, '0', 2) > 0 && $totalActiveDays > 0) {
            $summaryProfitPercent = bcdiv(bcmul($totalProfit, '100', 6), $totalGrossInvested, 2);
            $avgActiveDays = max(1, (int) round($totalActiveDays / max(1, count($accounts))));
            $summaryAnnualizedPercent = bcdiv(bcmul($summaryProfitPercent, '365', 6), (string) $avgActiveDays, 2);
        }

        return $this->json([
            'summary' => [
                'balance'           => $totalBalance,
                'profit'            => $totalProfit,
                'profitPercent'     => $summaryProfitPercent,
                'annualizedPercent' => $summaryAnnualizedPercent,
            ],
            'monthlyStats' => $monthlyStats,
            'accounts'     => $accountsData,
        ]);
    }

    /**
     * Calculates the number of days the account balance was positive (active days).
     * Ignores idle periods when balance was 0 or negative.
     *
     * @param array<array{sum: string, date: string}> $transactions sorted by date asc
     */
    private function calculateActiveDays(array $transactions): int
    {
        $balance = '0';
        $periodStart = null;
        $totalActiveDays = 0;
        $today = new \DateTimeImmutable('today');

        foreach ($transactions as $tx) {
            $prevBalance = $balance;
            $balance = bcadd($balance, (string) $tx['sum'], 2);
            $date = new \DateTimeImmutable($tx['date']);

            // Balance crossed from non-positive to positive: start active period
            if (bccomp($prevBalance, '0', 2) <= 0 && bccomp($balance, '0', 2) > 0) {
                $periodStart = $date;
            }

            // Balance crossed from positive to non-positive: end active period
            if (bccomp($prevBalance, '0', 2) > 0 && bccomp($balance, '0', 2) <= 0 && $periodStart !== null) {
                $totalActiveDays += $date->diff($periodStart)->days;
                $periodStart = null;
            }
        }

        // Account is still active — count up to today
        if ($periodStart !== null && bccomp($balance, '0', 2) > 0) {
            $totalActiveDays += $today->diff($periodStart)->days;
        }

        return max(1, $totalActiveDays);
    }

    #[Route('/deposits/delete/{id}', name: 'app_deposits_delete', methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $deposit = $this->depositRepository->getDepositByIdAndUserId($id, (int) $user?->getId());
        if (! $deposit) {
            throw $this->createNotFoundException('No deposits found for id ' . $id);
        }

        $this->depositRepository->remove($deposit);
        return $this->json(['success' => true]);
    }
}
