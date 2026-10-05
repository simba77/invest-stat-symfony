<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\Journal;

use App\Investments\Application\Response\Compiler\ManualOperationsListCompiler;
use App\Investments\Application\Response\DTO\Journal\ManualOperationItemDTO;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Journal\ManualOperationRepositoryInterface;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Application\Pagination\PaginatedResponseDTO;
use App\Shared\Application\Pagination\PaginationMetaFactory;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Symfony\NotFoundException;

/**
 * The journal of a manual account, the latest operations first.
 */
final readonly class GetAccountOperationsPageUseCase
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private ManualOperationRepositoryInterface $operationRepository,
        private ManualOperationsListCompiler $compiler,
        private PageRequestFactory $pageRequestFactory,
        private PaginationMetaFactory $paginationMetaFactory,
    ) {
    }

    /**
     * @return PaginatedResponseDTO<ManualOperationItemDTO>
     */
    public function execute(int $accountId, User $user, int $page = 1, int $perPage = PageRequestFactory::DEFAULT_PER_PAGE): PaginatedResponseDTO
    {
        $account = $this->accountRepository->getByIdAndUser($accountId, $user);
        if (! $account) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $accountId));
        }

        $totalItems = $this->operationRepository->countByAccount($account);
        $pageRequest = $this->pageRequestFactory->create($page, $perPage, $totalItems);
        $pagination = $this->paginationMetaFactory->create($pageRequest->page, $pageRequest->perPage, $totalItems);

        $operations = $this->operationRepository->findPageByAccount($account, $pageRequest->offset, $pageRequest->perPage);
        $lots = array_values(array_unique(array_filter(array_map(static fn ($operation) => $operation->getLot(), $operations))));
        $openings = $this->operationRepository->findOpenings($account, ...$lots);

        return new PaginatedResponseDTO(
            items: $this->compiler->compile(['operations' => $operations, 'openings' => $openings]),
            pagination: $pagination,
        );
    }
}
