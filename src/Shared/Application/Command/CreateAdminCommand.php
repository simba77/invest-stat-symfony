<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

use App\Shared\Domain\User;
use App\Shared\Domain\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'create-admin',
    description: 'Create an administrator',
)]
class CreateAdminCommand extends Command
{
    private const string EMAIL = 'admin@admin.com';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->userRepository->findByEmail(self::EMAIL) !== null) {
            $io->error(sprintf('The user "%s" already exists.', self::EMAIL));

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail(self::EMAIL);
        $user->setName('Admin Name');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'mypassword');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('The admin user was created successfully');

        return Command::SUCCESS;
    }
}
