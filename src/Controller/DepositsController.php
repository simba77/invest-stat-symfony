<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Deposit;
use App\Entity\DepositAccount;
use App\Entity\User;
use App\Request\DTO\Deposits\CreateDepositRequestDTO;
use App\Request\DTO\Deposits\UpdateDepositRequestDTO;
use App\Services\DepositsService;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class DepositsController extends AbstractController
{
    public function __construct(
        private readonly DepositsService $depositsService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/deposits', name: 'app_deposits_index', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $deposits = $this->depositsService->getAllDepositsForUser($user);
        return $this->json(['items' => $deposits]);
    }


    #[Route('/deposits/create', name: 'app_deposits_create', methods: ['POST'])]
    public function create(#[CurrentUser] ?User $user, #[MapRequestPayload] CreateDepositRequestDTO $dto): JsonResponse
    {
        $account = $this->entityManager->getRepository(DepositAccount::class)->findOneBy(['user' => $user, 'id' => $dto->accountId]);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $dto->accountId);
        }

        $deposit = new Deposit($dto->sum, $dto->type, $user, $account, Carbon::parse($dto->date));
        $this->entityManager->persist($deposit);
        $this->entityManager->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/deposits/get-form/{id}', name: 'app_deposits_get_form', methods: ['GET'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $form = $this->depositsService->getDepositForUser($id, $user);
        if (! $form) {
            throw $this->createNotFoundException('No deposits found for id ' . $id);
        }
        return $this->json($form);
    }

    #[Route('/deposits/update/{id}', name: 'app_deposits_update', methods: ['POST'])]
    public function update(int $id, #[MapRequestPayload] UpdateDepositRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $deposit = $this->entityManager->getRepository(Deposit::class)->findOneBy(['user' => $user, 'id' => $id]);
        if (! $deposit) {
            throw $this->createNotFoundException('No deposits found for id ' . $id);
        }
        $account = $this->entityManager->getRepository(DepositAccount::class)->findOneBy(['user' => $user, 'id' => $dto->accountId]);
        if (! $account) {
            throw $this->createNotFoundException('No accounts found for id ' . $dto->accountId);
        }

        $deposit->setSum($dto->sum);
        $deposit->setDepositAccount($account);
        $deposit->setType($dto->type);
        $deposit->setDate(Carbon::parse($dto->date));
        $this->entityManager->persist($deposit);
        $this->entityManager->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/deposits/delete/{id}', name: 'app_deposits_delete', methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $deposit = $this->entityManager->getRepository(Deposit::class)->findOneBy(['user' => $user, 'id' => $id]);
        if (! $deposit) {
            throw $this->createNotFoundException('No deposits found for id ' . $id);
        }

        $this->entityManager->remove($deposit);
        $this->entityManager->flush();
        return $this->json(['success' => true]);
    }
}
