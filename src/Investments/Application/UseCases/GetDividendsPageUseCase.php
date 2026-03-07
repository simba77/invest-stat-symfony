<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases;

use App\Investments\Application\Response\DTO\Operations\DividendListItemDTO;
use App\Investments\Domain\Operations\DividendRepositoryInterface;
use App\Shared\Application\Pagination\PaginatedResponseDTO;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Application\Pagination\PaginationMetaFactory;
use App\Shared\Domain\User;

final readonly class GetDividendsPageUseCase
{
    public function __construct(
        private DividendRepositoryInterface $dividendRepository,
        private PageRequestFactory $pageRequestFactory,
        private PaginationMetaFactory $paginationMetaFactory,
    ) {
    }

    /**
     * @return PaginatedResponseDTO<DividendListItemDTO>
     */
    public function execute(User $user, int $page = 1, int $perPage = PageRequestFactory::DEFAULT_PER_PAGE): PaginatedResponseDTO
    {
        $userId = $user->getId();
        $pageRequest = $this->pageRequestFactory->create($page, $perPage);

        $totalItems = $this->dividendRepository->countByUserId($userId);
        $pagination = $this->paginationMetaFactory->create($pageRequest->page, $pageRequest->perPage, $totalItems);

        $dividends = $this->dividendRepository->getPageByUserId($userId, $pageRequest->offset, $pageRequest->perPage);
        $items = [];

        foreach ($dividends as $dividend) {
            $items[] = new DividendListItemDTO(
                id: $dividend->getId(),
                date: $dividend->getDate()->format('d.m.Y'),
                ticker: $dividend->getTicker(),
                stockMarket: $dividend->getStockMarket(),
                amount: $dividend->getAmount(),
                accountName: $dividend->getAccount()->getName(),
            );
        }

        return new PaginatedResponseDTO(items: $items, pagination: $pagination);
    }
}
