<?php

declare(strict_types=1);

namespace App\Controller\Deals;

use App\Entity\Account;
use App\Entity\Deal;
use App\Entity\User;
use App\Request\DTO\Deals\CreateDealRequestDTO;
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
        private readonly EntityManagerInterface $em
    ) {
    }

    #[Route('/deals/{accountId}', name: 'app_deals_deals_index', requirements: ['accountId' => '\d+'])]
    public function index(int $accountId, #[CurrentUser] ?User $user): Response
    {
        return $this->json(['account' => [], 'items' => []]);
    }

    #[Route('/deals/create/{accountId}', name: 'app_deals_deals_create', requirements: ['accountId' => '\d+'], methods: ['POST'])]
    public function create(int $accountId, #[MapRequestPayload] CreateDealRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $account = $this->em->getRepository(Account::class)->find($accountId);
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
