<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\UseCases\Instruments\ListFutureMultipliersUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class FutureMultipliersController extends AbstractController
{
    public function __construct(
        private readonly ListFutureMultipliersUseCase $listFutureMultipliersUseCase
    ) {
    }

    #[Route('/futures/multipliers', name: 'app_future_multipliers_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $multipliers = $this->listFutureMultipliersUseCase->execute();
        return new JsonResponse($multipliers);
    }

    #[Route('/futures/multipliers/create', name: 'app_future_multipliers_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        return new JsonResponse([]);
    }

    #[Route('/futures/multipliers/update', name: 'app_future_multipliers_update', methods: ['PATCH'])]
    public function update(): JsonResponse
    {
        return new JsonResponse([]);
    }

    #[Route('/futures/multipliers/delete', name: 'app_future_multipliers_delete', methods: ['DELETE'])]
    public function delete(): JsonResponse
    {
        return new JsonResponse([]);
    }
}
