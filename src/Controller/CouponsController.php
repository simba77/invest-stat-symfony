<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Coupon;
use App\Entity\User;
use App\Request\DTO\Coupons\CreateCouponRequestDTO;
use App\Request\DTO\Coupons\UpdateCouponRequestDTO;
use App\Services\CouponsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class CouponsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CouponsService $couponsService
    ) {
    }

    #[Route('/coupons', name: 'app_coupons_index')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $items = $this->couponsService->getCouponsForUser($user);
        return $this->json(['items' => $items]);
    }

    #[Route('/coupons/create', name: 'app_coupons_create', requirements: ['categoryId' => '\d+'], methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateCouponRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $account = $this->em->getRepository(Account::class)->find($dto->accountId);
        $coupon = new Coupon(
            user:        $user,
            account:     $account,
            ticker:      $dto->ticker,
            stockMarket: $dto->stockMarket,
            amount:      $dto->amount,
            date:        new \DateTimeImmutable($dto->date),
        );

        $this->em->persist($coupon);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/coupons/get-form/{id}', name: 'app_coupons_get_form', requirements: ['id' => '\d+'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $form = [];
        if ($id > 0) {
            $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['id' => $id, 'user' => $user]);
            if (! $coupon) {
                throw $this->createNotFoundException('No coupons found for id ' . $id);
            }
            $form = [
                'id'          => $coupon->getId(),
                'amount'      => $coupon->getAmount(),
                'date'        => $coupon->getDate()->format('Y-m-d'),
                'accountId'   => $coupon->getAccount()->getId(),
                'ticker'      => $coupon->getTicker(),
                'stockMarket' => $coupon->getStockMarket(),
            ];
        }

        return $this->json($form);
    }

    #[Route('/coupons/update/{id}', name: 'app_coupons_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] UpdateCouponRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['id' => $id, 'user' => $user]);
        if (! $coupon) {
            throw $this->createNotFoundException('No coupons found for id ' . $id);
        }
        $account = $this->em->getRepository(Account::class)->find($dto->accountId);
        $coupon->setDate(new \DateTimeImmutable($dto->date));
        $coupon->setAmount($dto->amount);
        $coupon->setTicker($dto->ticker);
        $coupon->setStockMarket($dto->stockMarket);
        $coupon->setAccount($account);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/coupons/delete/{id}', name: 'app_coupons_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['id' => $id, 'user' => $user]);
        if (! $coupon) {
            throw $this->createNotFoundException('No coupons found for id ' . $id);
        }
        $this->em->remove($coupon);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

}
