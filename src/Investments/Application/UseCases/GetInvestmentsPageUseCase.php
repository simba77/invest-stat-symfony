<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases;

use App\Investments\Application\Response\DTO\Operations\InvestmentResponseDTO;
use App\Investments\Domain\Operations\InvestmentRepositoryInterface;
use App\Shared\Application\Pagination\PaginatedResponseDTO;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Application\Pagination\PaginationMetaFactory;
use App\Shared\Domain\User;

final readonly class GetInvestmentsPageUseCase
{
    public function __construct(
        private InvestmentRepositoryInterface $investmentRepository,
        private PageRequestFactory $pageRequestFactory,
        private PaginationMetaFactory $paginationMetaFactory,
    ) {
    }

    /**
     * @return PaginatedResponseDTO<InvestmentResponseDTO>
     */
    public function execute(User $user, int $page = 1, int $perPage = PageRequestFactory::DEFAULT_PER_PAGE): PaginatedResponseDTO
    {
        $userId = $user->getId();
        $pageRequest = $this->pageRequestFactory->create($page, $perPage);

        $totalItems = $this->investmentRepository->countByUserId($userId);
        $pagination = $this->paginationMetaFactory->create($pageRequest->page, $pageRequest->perPage, $totalItems);

        $investments = $this->investmentRepository->getPageByUserId($userId, $pageRequest->offset, $pageRequest->perPage);
        $items = [];

        foreach ($investments as $investment) {
            $items[] = new InvestmentResponseDTO(
                id: $investment['investment']->getId(),
                sum: $investment['investment']->getSum(),
                date: $investment['investment']->getDate()->format('d.m.Y'),
                account: $investment['account_name']
            );
        }

        return new PaginatedResponseDTO(items: $items, pagination: $pagination);
    }
}
