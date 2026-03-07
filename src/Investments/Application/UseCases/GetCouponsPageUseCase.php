<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases;

use App\Investments\Application\Response\Compiler\CouponListCompiler;
use App\Investments\Application\Response\DTO\Operations\CouponListItemDTO;
use App\Investments\Domain\Operations\CouponRepositoryInterface;
use App\Shared\Application\Pagination\PaginatedResponseDTO;
use App\Shared\Application\Pagination\PageRequestFactory;
use App\Shared\Application\Pagination\PaginationMetaFactory;
use App\Shared\Domain\User;

final readonly class GetCouponsPageUseCase
{
    public function __construct(
        private CouponRepositoryInterface $couponRepository,
        private CouponListCompiler $couponListCompiler,
        private PageRequestFactory $pageRequestFactory,
        private PaginationMetaFactory $paginationMetaFactory,
    ) {
    }

    /**
     * @return PaginatedResponseDTO<CouponListItemDTO>
     */
    public function execute(User $user, int $page = 1, int $perPage = PageRequestFactory::DEFAULT_PER_PAGE): PaginatedResponseDTO
    {
        $userId = $user->getId();
        $pageRequest = $this->pageRequestFactory->create($page, $perPage);

        $totalItems = $this->couponRepository->countByUserId($userId);
        $pagination = $this->paginationMetaFactory->create($pageRequest->page, $pageRequest->perPage, $totalItems);

        $coupons = $this->couponRepository->getPageByUserId($userId, $pageRequest->offset, $pageRequest->perPage);
        $items = $this->couponListCompiler->compile($coupons);

        return new PaginatedResponseDTO(items: $items, pagination: $pagination);
    }
}
