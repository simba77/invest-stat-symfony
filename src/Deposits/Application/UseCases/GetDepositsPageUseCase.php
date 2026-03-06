<?php

declare(strict_types=1);

namespace App\Deposits\Application\UseCases;

use App\Deposits\Application\Response\Compiler\DepositsListItemsCompiler;
use App\Deposits\Application\Response\DTO\DepositListItemDTO;
use App\Deposits\Domain\DepositRepositoryInterface;
use App\Shared\Application\Pagination\PaginatedResponseDTO;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Application\Pagination\PaginationMetaFactory;
use App\Shared\Domain\User;

final readonly class GetDepositsPageUseCase
{
    public function __construct(
        private DepositRepositoryInterface $depositRepository,
        private DepositsListItemsCompiler $depositsListCompiler,
        private PageRequestFactory $pageRequestFactory,
        private PaginationMetaFactory $paginationMetaFactory,
    ) {
    }

    /**
     * @return PaginatedResponseDTO<DepositListItemDTO>
     */
    public function execute(User $user, int $page = 1, int $perPage = PageRequestFactory::DEFAULT_PER_PAGE): PaginatedResponseDTO
    {
        $userId = $user->getId();
        $pageRequest = $this->pageRequestFactory->create($page, $perPage);

        $totalItems = $this->depositRepository->countByUserId($userId);
        $pagination = $this->paginationMetaFactory->create($pageRequest->page, $pageRequest->perPage, $totalItems);

        $deposits = $this->depositRepository->getDepositsPageForUserId($userId, $pageRequest->offset, $pageRequest->perPage);

        /** @var list<DepositListItemDTO> $items */
        $items = $this->depositsListCompiler->compile($deposits);

        return new PaginatedResponseDTO(items: $items, pagination: $pagination);
    }
}
