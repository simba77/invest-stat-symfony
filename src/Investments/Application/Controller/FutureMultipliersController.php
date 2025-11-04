<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\Request\DTO\Instruments\CreateFutureMultiplierRequestDTO;
use App\Investments\Application\UseCases\Instruments\CreateFutureMultiplierUseCase;
use App\Investments\Application\UseCases\Instruments\DeleteFutureMultiplierUseCase;
use App\Investments\Application\UseCases\Instruments\ListFutureMultipliersUseCase;
use App\Investments\Domain\Instruments\Exceptions\FutureMultiplierAlreadyExistsException;
use App\Investments\Domain\Instruments\Exceptions\FutureMultiplierNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class FutureMultipliersController extends AbstractController
{
    public function __construct(
        private readonly ListFutureMultipliersUseCase $listFutureMultipliersUseCase,
        private readonly CreateFutureMultiplierUseCase $createFutureMultiplierUseCase,
        private readonly DeleteFutureMultiplierUseCase $deleteFutureMultiplierUseCase,
    ) {
    }

    #[Route('/futures/multipliers', name: 'app_future_multipliers_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $multipliers = $this->listFutureMultipliersUseCase->execute();
        return new JsonResponse($multipliers);
    }

    #[Route('/futures/multipliers/create', name: 'app_future_multipliers_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload]
        CreateFutureMultiplierRequestDTO $requestDTO
    ): JsonResponse {
        try {
            $this->createFutureMultiplierUseCase->execute($requestDTO->ticker, $requestDTO->value);
        } catch (FutureMultiplierAlreadyExistsException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return new JsonResponse(['success' => true]);
    }

    #[Route('/futures/multipliers/update', name: 'app_future_multipliers_update', methods: ['PATCH'])]
    public function update(): JsonResponse
    {
        return new JsonResponse([]);
    }

    #[Route('/futures/multipliers/get-form/{id}', name: 'app_future_multipliers_get_form', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getForm(int $id): JsonResponse
    {
        return new JsonResponse(
            [
                'id'     => $id,
                'ticker' => '',
                'value'  => '',
            ]
        );
    }

    #[Route('/futures/multipliers/delete/{id}', name: 'app_future_multipliers_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->deleteFutureMultiplierUseCase->execute($id);
        } catch (FutureMultiplierNotFoundException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return new JsonResponse(['success' => true]);
    }
}
