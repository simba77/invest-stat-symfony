<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\Request\DTO\Operations\DealsFilterRequestDTO;
use App\Investments\Application\Response\Compiler\ClosedDealsListCompiler;
use App\Investments\Application\Response\Compiler\MonthlyDealsListCompiler;
use App\Investments\Domain\Operations\CouponRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\DividendRepositoryInterface;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class AnalyticsController extends AbstractController
{
    public function __construct(
        public readonly DealRepositoryInterface $dealRepository,
        public readonly ClosedDealsListCompiler $closedDealsListCompiler,
        public readonly MonthlyDealsListCompiler $monthlyDealsListCompiler,
        public readonly DividendRepositoryInterface $dividendRepository,
        public readonly CouponRepositoryInterface $couponRepository,
    ) {
    }

    #[Route('/analytics/closed-deals', name: 'app_analytics_closed_deals', methods: 'GET')]
    public function closedDeals(#[MapQueryString] ?DealsFilterRequestDTO $filter, #[CurrentUser] ?User $user): JsonResponse
    {
        $deals = $this->dealRepository->getClosedDealsForUserByFilter($user->getId(), $filter);
        return $this->json($this->closedDealsListCompiler->compile($deals));
    }

    #[Route('/analytics/monthly-closed-deals', name: 'app_analytics_monthly_closed_deals', methods: 'GET')]
    public function monthlyClosedDeals(#[MapQueryString] ?DealsFilterRequestDTO $filter, #[CurrentUser] ?User $user): JsonResponse
    {
        return $this->json(
            [
                'profitByMonths' => $this->monthlyDealsListCompiler->compile(
                    [
                        'deals'     => $this->dealRepository->getClosedDealsForUserByFilter($user->getId(), $filter),
                        'dividends' => $this->dividendRepository->findAll(),
                        'coupons'   => $this->couponRepository->findAll(),
                    ],
                ),
            ]
        );
    }
}
