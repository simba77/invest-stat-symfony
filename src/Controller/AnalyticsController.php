<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Request\DTO\Deals\DealsFilterRequestDTO;
use App\Services\Deals\ClosedDealsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AnalyticsController extends AbstractController
{
    #[Route('/analytics/closed-deals', name: 'app_analytics_closed_deals', methods: 'GET')]
    public function closedDeals(
        ClosedDealsService $dealsService,
        #[MapQueryString] ?DealsFilterRequestDTO $filter,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        $deals = $dealsService->getDeals($user, $filter);
        return $this->json($deals);
    }
}
