<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\Request\DTO\Operations\CreateDividendRequestDTO;
use App\Investments\Application\Request\DTO\Operations\UpdateDividendRequestDTO;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\DividendsService;
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
class DividendsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DividendsService $dividendsService
    ) {
    }

    #[Route('/dividends', name: 'app_dividends_index')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $items = $this->dividendsService->getDividendsForUser($user);
        return $this->json(['items' => $items]);
    }

    #[Route('/dividends/create', name: 'app_dividends_create', requirements: ['categoryId' => '\d+'], methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateDividendRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $account = $this->em->getRepository(Account::class)->find($dto->accountId);
        $dividend = new Dividend(
            user:        $user,
            account:     $account,
            ticker:      $dto->ticker,
            stockMarket: $dto->stockMarket,
            amount:      $dto->amount,
            date:        new \DateTimeImmutable($dto->date),
        );

        $this->em->persist($dividend);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/dividends/get-form/{id}', name: 'app_dividends_getform', requirements: ['id' => '\d+'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $form = [];
        if ($id > 0) {
            $dividend = $this->em->getRepository(Dividend::class)->findOneBy(['id' => $id, 'user' => $user]);
            if (! $dividend) {
                throw $this->createNotFoundException('No dividend found for id ' . $id);
            }
            $form = [
                'id'          => $dividend->getId(),
                'amount'      => $dividend->getAmount(),
                'date'        => $dividend->getDate()->format('Y-m-d'),
                'accountId'   => $dividend->getAccount()->getId(),
                'ticker'      => $dividend->getTicker(),
                'stockMarket' => $dividend->getStockMarket(),
            ];
        }

        return $this->json($form);
    }

    #[Route('/dividends/update/{id}', name: 'app_dividends_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] UpdateDividendRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $dividend = $this->em->getRepository(Dividend::class)->findOneBy(['id' => $id, 'user' => $user]);
        if (! $dividend) {
            throw $this->createNotFoundException('No dividend found for id ' . $id);
        }
        $account = $this->em->getRepository(Account::class)->find($dto->accountId);
        $dividend->setDate(new \DateTimeImmutable($dto->date));
        $dividend->setAmount($dto->amount);
        $dividend->setTicker($dto->ticker);
        $dividend->setStockMarket($dto->stockMarket);
        $dividend->setAccount($account);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/dividends/delete/{id}', name: 'app_dividends_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $investment = $this->em->getRepository(Dividend::class)->findOneBy(['id' => $id, 'user' => $user]);
        if (! $investment) {
            throw $this->createNotFoundException('No dividend found for id ' . $id);
        }
        $this->em->remove($investment);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

}
