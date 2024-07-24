<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Investment;
use App\Entity\User;
use App\Request\DTO\Investments\InvestmentRequestDTO;
use App\Services\AccountService;
use App\Services\DividendsService;
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
        private readonly DividendsService $dividendsService,
        private readonly AccountService $accountService
    ) {
    }

    #[Route('/dividends', name: 'app_dividends_index')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        $items = $this->dividendsService->getDividendsForUser($user);
        return $this->json(['items' => $items]);
    }

    #[Route('/dividends/create', name: 'app_dividends_create', requirements: ['categoryId' => '\d+'], methods: ['POST'])]
    public function create(#[MapRequestPayload] InvestmentRequestDTO $dto, #[CurrentUser] ?User $user): Response
    {
        $account = $this->em->getRepository(Account::class)->find($dto->account);
        $inv = new Investment($dto->sum, new \DateTimeImmutable($dto->date), $account, $user->getId());
        $this->em->persist($inv);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/dividends/get-form/{id}', name: 'app_dividends_getform', requirements: ['id' => '\d+'])]
    public function getForm(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $form = [];
        if ($id > 0) {
            $investment = $this->em->getRepository(Investment::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
            if (! $investment) {
                throw $this->createNotFoundException('No investment found for id ' . $id);
            }
            $form = [
                'id'      => $investment->getId(),
                'sum'     => $investment->getSum(),
                'date'    => $investment->getDate()->format('Y-m-d'),
                'account' => $investment->getAccount()->getId(),
            ];
        }

        $accounts = $this->accountService->getSimpleListOfAccountsForUser($user);
        return $this->json(['form' => $form, 'accounts' => $accounts]);
    }

    #[Route('/dividends/edit/{id}', name: 'app_dividends_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, #[MapRequestPayload] InvestmentRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $investment = $this->em->getRepository(Investment::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
        if (! $investment) {
            throw $this->createNotFoundException('No investment found for id ' . $id);
        }
        $account = $this->em->getRepository(Account::class)->find($dto->account);
        $investment->setDate(new \DateTimeImmutable($dto->date));
        $investment->setSum($dto->sum);
        $investment->setAccount($account);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/dividends/delete/{id}', name: 'app_dividends_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $investment = $this->em->getRepository(Investment::class)->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
        if (! $investment) {
            throw $this->createNotFoundException('No investment found for id ' . $id);
        }
        $this->em->remove($investment);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

}
