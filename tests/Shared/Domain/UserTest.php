<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain;

use App\Shared\Domain\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    /**
     * @dataProvider storedRoles
     *
     * @param list<string> $stored
     * @param list<string> $expected
     */
    public function testEveryUserHasUserRoleExactlyOnce(array $stored, array $expected): void
    {
        $user = (new User())->setRoles($stored);

        self::assertSame($expected, array_values($user->getRoles()));
    }

    /**
     * @return iterable<string, array{list<string>, list<string>}>
     */
    public static function storedRoles(): iterable
    {
        yield 'no roles stored' => [[], ['ROLE_USER']];
        yield 'extra role' => [['ROLE_ADMIN'], ['ROLE_ADMIN', 'ROLE_USER']];
        yield 'user role stored explicitly' => [['ROLE_USER'], ['ROLE_USER']];
    }
}
