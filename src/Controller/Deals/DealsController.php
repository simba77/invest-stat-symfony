<?php

declare(strict_types=1);

namespace App\Controller\Deals;

use App\Entity\Account;
use App\Entity\Deal;
use App\Entity\User;
use App\Repository\DealRepository;
use App\Request\DTO\Deals\CreateDealRequestDTO;
use App\Services\AccountService;
use App\Services\Deals\DealsListService;
use App\Services\Deals\DealStatus;
use App\Services\Deals\DealType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class DealsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccountService $accountService,
        private readonly DealsListService $dealsListService
    ) {
    }

    #[Route('/deals/{accountId}', name: 'app_deals_deals_index', requirements: ['accountId' => '\d+'])]
    public function index(int $accountId, #[CurrentUser] ?User $user): Response
    {
        $acc = $this->em->getRepository(Account::class)->findOneBy(['id' => $accountId, 'userId' => $user->getId()]);
        $deals = $this->dealsListService->getListWithGroups($user, $acc);

        $account = $this->accountService->getAccountWithDetailInformation($accountId, $user->getId());
        return $this->json(['account' => $account, 'deals'=> $deals]);
    }

    #[Route('/deals/create/{accountId}', name: 'app_deals_deals_create', requirements: ['accountId' => '\d+'], methods: ['POST'])]
    public function create(int $accountId, #[MapRequestPayload] CreateDealRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $account = $this->em->getRepository(Account::class)->findOneBy(['id' => $accountId, 'userId' => $user->getId()]);
        if (! $account) {
            throw $this->createNotFoundException('No account found for id ' . $accountId);
        }

        $deal = new Deal(
            $user,
            $account,
            $dto->ticker,
            $dto->stockMarket,
            DealStatus::Active,
            $dto->isShort ? DealType::Short : DealType::Long,
            $dto->quantity,
            $dto->buyPrice,
            $dto->targetPrice
        );

        $this->em->persist($deal);
        $this->em->flush();

        return $this->json(['success' => true]);
    }
}
