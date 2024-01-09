<?php

declare(strict_types=1);

namespace App\Controller;

use App\Services\MarketData\Securities\SecuritiesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class SecuritiesController extends AbstractController
{
    public function __construct(
        private readonly SecuritiesService $securitiesService
    ) {
    }

    #[Route('/securities/get-data-by-ticker/{ticker}', name: 'app_securities_getsecuritybyticker', methods: ['GET'])]
    public function getSecurityByTicker(string $ticker): JsonResponse
    {
        $securityData = $this->securitiesService->getSecurityByTicker($ticker);
        return $this->json(['security' => $securityData]);
    }
}
