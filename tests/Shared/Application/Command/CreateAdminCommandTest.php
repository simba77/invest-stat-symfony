<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command;

use App\Shared\Domain\User;
use App\Tests\Fixtures\UserFixtures;
use App\Tests\Support\InteractsWithDatabase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateAdminCommandTest extends KernelTestCase
{
    use InteractsWithDatabase;

    public function testCreatesAdminWhoCanLogIn(): void
    {
        // The fixtures already contain the admin account
        $this->entityManager()->remove($this->admin());
        $this->entityManager()->flush();
        $command = $this->command();

        $command->execute([]);

        $command->assertCommandIsSuccessful();
        $admins = $this->findFreshBy(User::class, ['email' => UserFixtures::ADMIN_EMAIL]);
        self::assertCount(1, $admins);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($passwordHasher->isPasswordValid($admins[0], 'mypassword'));
    }

    public function testRefusesToCreateAdminTwice(): void
    {
        $command = $this->command();

        $command->execute([]);

        self::assertSame(Command::FAILURE, $command->getStatusCode());
        self::assertStringContainsString('already exists', $command->getDisplay());
        self::assertCount(1, $this->findFreshBy(User::class, ['email' => UserFixtures::ADMIN_EMAIL]));
    }

    private function command(): CommandTester
    {
        return new CommandTester((new Application(static::$kernel ?? static::bootKernel()))->find('create-admin'));
    }
}
