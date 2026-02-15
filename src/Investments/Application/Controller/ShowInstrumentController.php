<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\UseCases\Instruments\ShowShareUseCase;
use App\Investments\Domain\Instruments\Exceptions\InstrumentNotFoundException;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
#[Route('/instrument/share/{id}', name: 'app_instrument_show', requirements: ['id' => '\d+'], methods: ['GET'])]
final class ShowInstrumentController extends AbstractController
{
    public function __construct(
        public ShowShareUseCase $showShareUseCase,
    ) {
    }

    public function __invoke(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        try {
        $share = $this->showShareUseCase->execute($id, $user->getId());
        } catch (InstrumentNotFoundException) {
            return new JsonResponse(['message' => 'Instrument not found'], 404);
        }

        return new JsonResponse($share);
    }
}
