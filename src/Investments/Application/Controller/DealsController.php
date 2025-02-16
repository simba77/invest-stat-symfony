<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\Request\DTO\Operations\CreateDealRequestDTO;
use App\Investments\Application\Request\DTO\Operations\EditDealRequestDTO;
use App\Investments\Application\Request\DTO\Operations\SellDealRequestDTO;
use App\Investments\Application\Response\DTO\Compiler\AccountItemCompiler;
use App\Investments\Application\Response\DTO\Operations\EditDealDTO;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealService;
use App\Investments\Domain\Operations\Deals\DealsListService;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Shared\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class DealsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DealsListService $dealsListService,
        private readonly DealService $dealService,
        protected readonly AccountRepositoryInterface $accountRepository,
        protected readonly AccountItemCompiler $accountItemCompiler,
    ) {
    }

    #[Route('/deals/{accountId}', name: 'app_deals_deals_index', requirements: ['accountId' => '\d+'])]
    public function dealListByAccount(int $accountId, #[CurrentUser] ?User $user): Response
    {
        $account = $this->accountRepository->findByIdAndUserWithDeposits($accountId, $user);
        if (! $account) {
            throw $this->createNotFoundException('No account found for id ' . $accountId);
        }
        $deals = $this->dealsListService->getListWithGroups($user, $account['account']);
        return $this->json(
            [
                'account' => $this->accountItemCompiler->compile($account),
                'deals'   => $deals,
            ]
        );
    }

    #[Route('/deals/create/{accountId}', name: 'app_deals_deals_create', requirements: ['accountId' => '\d+'], methods: ['POST'])]
    public function create(int $accountId, #[MapRequestPayload] CreateDealRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $account = $this->em->getRepository(Account::class)->findOneBy(['id' => $accountId, 'userId' => $user->getId()]);
        if (! $account) {
            throw $this->createNotFoundException('No account found for id ' . $accountId);
        }

        $this->dealService->addDeal($account, $user, $dto);
        return $this->json(['success' => true]);
    }

    #[Route('/deals/delete/{id}', name: 'app_deals_deals_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $deal = $this->em->getRepository(Deal::class)->findOneBy(['id' => $id, 'user' => $user]);
        if (! $deal) {
            throw $this->createNotFoundException('No expense found for id ' . $id);
        }
        $this->em->remove($deal);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/deals/get-by-id/{id}', name: 'app_deals_deals_getbyid', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getById(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $deal = $this->em->getRepository(Deal::class)->findOneBy(['id' => $id, 'user' => $user]);
        if (! $deal) {
            throw $this->createNotFoundException('No deal found for id ' . $id);
        }
        return $this->json(
            [
                'deal' => new EditDealDTO(
                    id:          $deal->getId(),
                    ticker:      $deal->getTicker(),
                    stockMarket: $deal->getStockMarket(),
                    quantity:    $deal->getQuantity(),
                    buyPrice:    $deal->getBuyPrice(),
                    targetPrice: $deal->getTargetPrice(),
                    isShort:     $deal->getType() === DealType::Short
                ),
            ]
        );
    }

    #[Route('/deals/edit/{id}', name: 'app_deals_deals_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] EditDealRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $deal = $this->em->getRepository(Deal::class)->findOneBy(['id' => $id, 'user' => $user]);
        if (! $deal) {
            throw $this->createNotFoundException('No deal found for id ' . $id);
        }

        $this->dealService->changeDeal($deal, $dto);

        return $this->json(['success' => true]);
    }

    #[Route('/deals/sell', name: 'app_deals_deals_sell', methods: ['POST'])]
    public function sell(#[MapRequestPayload] SellDealRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($dto->id) {
            // Sell one deal
            $deal = $this->em->getRepository(Deal::class)->findOneBy(['id' => $dto->id, 'user' => $user]);
            if (! $deal) {
                throw $this->createNotFoundException('No deal found for id ' . $dto->id);
            }
            $this->dealService->sellOne($deal, $dto);
        } else {
            // Sell the required number of securities
            $account = $this->em->getRepository(Account::class)->findOneBy(['id' => $dto->accountId, 'userId' => $user->getId()]);
            $this->dealService->sellAsNeeded($user, $account, $dto);
        }

        return $this->json(['success' => true]);
    }
}
