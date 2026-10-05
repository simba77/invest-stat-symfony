<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\UseCases\GetOperationsHistoryPageUseCase;
use App\Investments\Domain\Operations\History\OperationCategory;
use App\Investments\Domain\Operations\History\OperationHistoryFilter;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
final class OperationsHistoryController extends AbstractController
{
    public function __construct(
        private readonly GetOperationsHistoryPageUseCase $getOperationsHistoryPageUseCase,
    ) {
    }

    #[Route('/operations', name: 'app_operations_history', methods: ['GET'])]
    public function index(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = $request->query->getInt('perPage', PageRequestFactory::DEFAULT_PER_PAGE);
        $accountId = $request->query->getInt('accountId');
        // An unknown category shows everything, like no category
        $filter = new OperationHistoryFilter(
            accountId: $accountId > 0 ? $accountId : null,
            category:  OperationCategory::tryFrom($request->query->getString('category')),
        );

        return $this->json($this->getOperationsHistoryPageUseCase->execute($user, $filter, $page, $perPage));
    }
}
