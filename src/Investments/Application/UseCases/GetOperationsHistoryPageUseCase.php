<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases;

use App\Investments\Application\Response\Compiler\OperationHistoryListCompiler;
use App\Investments\Application\Response\DTO\History\OperationHistoryItemDTO;
use App\Investments\Domain\Operations\History\OperationHistoryFilter;
use App\Investments\Domain\Operations\History\OperationHistoryRepositoryInterface;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Application\Pagination\PaginatedResponseDTO;
use App\Shared\Application\Pagination\PaginationMetaFactory;
use App\Shared\Domain\User;

/**
 * The operations of all the accounts of the user, the latest first.
 */
final readonly class GetOperationsHistoryPageUseCase
{
    public function __construct(
        private OperationHistoryRepositoryInterface $historyRepository,
        private OperationHistoryListCompiler $compiler,
        private PageRequestFactory $pageRequestFactory,
        private PaginationMetaFactory $paginationMetaFactory,
    ) {
    }

    /**
     * @return PaginatedResponseDTO<OperationHistoryItemDTO>
     */
    public function execute(User $user, OperationHistoryFilter $filter, int $page = 1, int $perPage = PageRequestFactory::DEFAULT_PER_PAGE): PaginatedResponseDTO
    {
        $userId = (int) $user->getId();
        $totalItems = $this->historyRepository->count($userId, $filter);
        $pageRequest = $this->pageRequestFactory->create($page, $perPage, $totalItems);
        $pagination = $this->paginationMetaFactory->create($pageRequest->page, $pageRequest->perPage, $totalItems);

        $entries = $totalItems > 0 ? $this->historyRepository->findPage($userId, $filter, $pageRequest->offset, $pageRequest->perPage) : [];

        return new PaginatedResponseDTO(items: $this->compiler->compile($entries), pagination: $pagination);
    }
}
