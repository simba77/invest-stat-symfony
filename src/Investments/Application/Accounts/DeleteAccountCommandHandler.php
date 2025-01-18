<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteAccountCommandHandler
{
    public function __construct(
        public readonly AccountRepositoryInterface $accountRepository
    ) {
    }

    public function __invoke(DeleteAccountCommand $command): void
    {
        $account = $this->accountRepository->getByIdAndUser($command->accountId, $command->user);
        if (! $account) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $command->accountId));
        }
        $this->accountRepository->remove($account);
    }
}
