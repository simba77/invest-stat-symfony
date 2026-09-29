<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine;

use App\Deposits\Domain\DepositAccount;
use App\Shared\Domain\User;
use App\Tests\Support\InteractsWithDatabase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Entities implementing the Created/Updated provider interfaces get their audit columns
 * filled by Doctrine listeners: the time from the clock, the author from the security token.
 */
final class AuditListenersTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use InteractsWithDatabase;

    public function testStampsCreationTimeAndAuthor(): void
    {
        self::mockTime('2025-03-01 10:00:00');
        $admin = $this->admin();
        $this->authenticate($admin);

        $account = new DepositAccount('Bank', $admin);
        $this->persist($account);

        $saved = $this->findFresh(DepositAccount::class, $account->getId());
        self::assertNotNull($saved);
        self::assertEquals(new \DateTimeImmutable('2025-03-01 10:00:00'), $saved->createdAt());
        self::assertSame($admin->getId(), $saved->createdBy());
        self::assertNull($saved->updatedAt());
        self::assertNull($saved->updatedBy());
    }

    public function testLeavesAuthorEmptyWithoutAuthenticatedUser(): void
    {
        // Console commands and message handlers run without a security token
        self::mockTime('2025-03-01 10:00:00');

        $account = new DepositAccount('Bank', $this->admin());
        $this->persist($account);

        $saved = $this->findFresh(DepositAccount::class, $account->getId());
        self::assertNotNull($saved);
        self::assertEquals(new \DateTimeImmutable('2025-03-01 10:00:00'), $saved->createdAt());
        self::assertNull($saved->createdBy());
    }

    public function testStampsUpdateTimeAndAuthorWithoutTouchingCreation(): void
    {
        self::mockTime('2025-03-01 10:00:00');
        $admin = $this->admin();
        $account = new DepositAccount('Bank', $admin);
        $this->persist($account);

        self::mockTime('2025-03-05 18:30:00');
        $this->authenticate($admin);
        $account->setName('Savings');
        $this->entityManager()->flush();

        $saved = $this->findFresh(DepositAccount::class, $account->getId());
        self::assertNotNull($saved);
        self::assertEquals(new \DateTimeImmutable('2025-03-01 10:00:00'), $saved->createdAt());
        self::assertNull($saved->createdBy());
        self::assertEquals(new \DateTimeImmutable('2025-03-05 18:30:00'), $saved->updatedAt());
        self::assertSame($admin->getId(), $saved->updatedBy());
    }

    private function authenticate(User $user): void
    {
        static::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles()),
        );
    }
}
