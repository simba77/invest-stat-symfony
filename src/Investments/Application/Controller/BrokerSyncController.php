<?php

declare(strict_types=1);

namespace App\Investments\Application\Controller;

use App\Investments\Application\BrokerSync\DeleteBrokerSyncSettingsCommand;
use App\Investments\Application\BrokerSync\SaveBrokerSyncSettingsCommand;
use App\Investments\Application\Request\DTO\BrokerSync\BrokerSyncSettingsRequestDTO;
use App\Investments\Application\Request\DTO\BrokerSync\ExternalAccountsRequestDTO;
use App\Investments\Application\Response\Compiler\BrokerSyncPageCompiler;
use App\Investments\Application\UseCases\BrokerSync\ListExternalAccountsUseCase;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerAccountLinkRepositoryInterface;
use App\Shared\Domain\Bus\SyncCommandBusInterface;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
final class BrokerSyncController extends AbstractController
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly BrokerAccountLinkRepositoryInterface $linkRepository,
        private readonly BrokerSyncPageCompiler $brokerSyncPageCompiler,
        private readonly ListExternalAccountsUseCase $listExternalAccountsUseCase,
        private readonly SyncCommandBusInterface $commandBus,
    ) {
    }

    #[Route('/accounts/{id}/broker-sync', name: 'app_accounts_broker_sync_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $user = $this->user($user);
        $link = $this->linkRepository->findByAccount($this->account($id, $user));

        return $this->json($this->brokerSyncPageCompiler->compile($link));
    }

    #[Route('/accounts/{id}/broker-sync', name: 'app_accounts_broker_sync_save', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function save(int $id, #[MapRequestPayload] BrokerSyncSettingsRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $user = $this->user($user);
        $link = $this->linkRepository->findByAccount($this->account($id, $user));
        if ($link === null && $dto->newToken() === null) {
            throw $this->tokenRequired($dto);
        }

        $this->commandBus->dispatch(
            new SaveBrokerSyncSettingsCommand(
                accountId:         $id,
                user:              $user,
                provider:          $dto->provider(),
                token:             $dto->newToken(),
                externalAccountId: $dto->externalAccountId,
                feeAllocation:     $dto->feeAllocation(),
                enabled:           $dto->enabled,
            )
        );

        return $this->json(['success' => true]);
    }

    #[Route('/accounts/{id}/broker-sync/delete', name: 'app_accounts_broker_sync_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteBrokerSyncSettingsCommand($id, $this->user($user)));

        return $this->json(['success' => true]);
    }

    #[Route('/accounts/{id}/broker-sync/external-accounts', name: 'app_accounts_broker_sync_external_accounts', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function externalAccounts(int $id, #[MapRequestPayload] ExternalAccountsRequestDTO $dto, #[CurrentUser] ?User $user): JsonResponse
    {
        $link = $this->linkRepository->findByAccount($this->account($id, $this->user($user)));
        if ($dto->newToken() === null && ! $this->hasStoredToken($link, $dto)) {
            throw $this->tokenRequired($dto);
        }

        return $this->json(['items' => $this->listExternalAccountsUseCase->execute($dto->provider(), $dto->newToken(), $link)]);
    }

    private function user(?User $user): User
    {
        return $user ?? throw $this->createAccessDeniedException('Authentication required.');
    }

    private function account(int $id, User $user): Account
    {
        return $this->accountRepository->getByIdAndUser($id, $user)
            ?? throw $this->createNotFoundException('No account found for id ' . $id);
    }

    private function hasStoredToken(?BrokerAccountLink $link, ExternalAccountsRequestDTO $dto): bool
    {
        return $link !== null && $link->getProvider() === $dto->provider();
    }

    /**
     * Reported like the request validation, so that the form shows it next to the token field.
     */
    private function tokenRequired(object $dto): UnprocessableEntityHttpException
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Enter the API token.', null, [], $dto, 'token', null),
        ]);

        return new UnprocessableEntityHttpException('Validation failed', new ValidationFailedException($dto, $violations));
    }
}
