<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Controller;

use App\Shared\Domain\TaxProfile;
use App\Shared\Domain\User;
use App\Tests\Fixtures\UserFixtures;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthControllerTest extends ApiTestCase
{
    private const string REMEMBER_ME_COOKIE = 'REMEMBERME';
    private const int ONE_YEAR = 365 * 24 * 60 * 60;

    public function testLoginReturnsCurrentUser(): void
    {
        $admin = $this->admin();

        $this->logIn(rememberMe: false);

        self::assertResponseIsSuccessful();
        self::assertSame([
            'id'         => $admin->getId(),
            'email'      => UserFixtures::ADMIN_EMAIL,
            'name'       => 'Admin Name',
            'salary'     => null,
            'taxProfile' => $admin->getTaxProfile()->value,
        ], $this->responseJson());
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $this->logIn(rememberMe: true, password: 'wrong password');

        self::assertResponseStatusCodeSame(401);
        self::assertNull($this->rememberMeCookie());
    }

    public function testLoginWithoutRememberMeSetsNoCookie(): void
    {
        $this->logIn(rememberMe: false);

        self::assertResponseIsSuccessful();
        self::assertNull($this->rememberMeCookie());
    }

    public function testLoginWithRememberMeKeepsDeviceLoggedInForAYear(): void
    {
        $this->logIn(rememberMe: true);

        self::assertResponseIsSuccessful();
        $cookie = $this->rememberMeCookie();
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertEqualsWithDelta(time() + self::ONE_YEAR, (int) $cookie->getExpiresTime(), 60);
    }

    public function testRememberMeRestoresAuthenticationWhenSessionIsGone(): void
    {
        $this->logIn(rememberMe: true);
        $this->continueWithOnly($this->rememberMeCookie());

        $this->getJson('/api/login');

        self::assertResponseIsSuccessful();
        self::assertSame(UserFixtures::ADMIN_EMAIL, $this->responseJson()['email']);
    }

    public function testRememberMeCookieStaysValidAfterItWasUsed(): void
    {
        // A browser may keep sending a cookie that was already used, e.g. when the response
        // with the renewed one never arrived. That must not log the device out.
        $this->logIn(rememberMe: true);
        $cookie = $this->rememberMeCookie();

        $this->continueWithOnly($cookie);
        $this->getJson('/api/login');
        self::assertResponseIsSuccessful();

        $this->continueWithOnly($cookie);
        $this->getJson('/api/login');
        self::assertResponseIsSuccessful();
    }

    public function testLogoutForgetsOnlyTheCurrentDevice(): void
    {
        $this->logIn(rememberMe: true);
        $otherDevice = $this->rememberMeCookie();
        $this->client->getCookieJar()->clear();
        $this->logIn(rememberMe: true);

        $this->getJson('/api/logout');

        self::assertNull($this->rememberMeCookie());
        $this->continueWithOnly($otherDevice);
        $this->getJson('/api/login');
        self::assertResponseIsSuccessful();
    }

    public function testPasswordChangeLogsOutEveryDevice(): void
    {
        $this->logIn(rememberMe: true);
        $cookie = $this->rememberMeCookie();
        $admin = $this->admin();
        $admin->setPassword($this->passwordHasher()->hashPassword($admin, 'new password'));
        $this->persist($admin);

        $this->continueWithOnly($cookie);
        $this->getJson('/api/login');

        self::assertResponseStatusCodeSame(401);
    }

    public function testChangeProfileRejectsAnonymousRequest(): void
    {
        $this->postJson('/api/change-profile', $this->profile());

        self::assertResponseStatusCodeSame(403);
    }

    public function testChangeProfileUpdatesUserAndKeepsPasswordWhenItIsEmpty(): void
    {
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/change-profile', $this->profile());

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        $user = $this->findFresh(User::class, $admin->getId());
        self::assertNotNull($user);
        self::assertSame('New Name', $user->getName());
        self::assertSame('new@example.com', $user->getEmail());
        self::assertSame('150000.00', $user->getSalary());
        self::assertSame(TaxProfile::Ndfl15, $user->getTaxProfile());
        self::assertTrue($this->passwordHasher()->isPasswordValid($user, UserFixtures::PASSWORD));
    }

    public function testChangeProfileChangesPasswordWhenGiven(): void
    {
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/change-profile', $this->profile(['password' => 'new password']));

        self::assertResponseIsSuccessful();
        $user = $this->findFresh(User::class, $admin->getId());
        self::assertNotNull($user);
        self::assertTrue($this->passwordHasher()->isPasswordValid($user, 'new password'));
    }

    public function testChangeProfileAllowsUserWithoutSalary(): void
    {
        // The form sends null when the salary field is empty
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/change-profile', $this->profile(['salary' => null]));

        self::assertResponseIsSuccessful();
        self::assertNull($this->findFresh(User::class, $admin->getId())?->getSalary());
    }

    /**
     * @dataProvider invalidProfiles
     *
     * @param array<string, mixed> $overrides
     */
    public function testChangeProfileRejectsInvalidData(array $overrides, string $violatedField): void
    {
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/change-profile', $this->profile($overrides));

        $this->assertViolatedFields([$violatedField]);
        self::assertSame(UserFixtures::ADMIN_EMAIL, $this->findFresh(User::class, $admin->getId())?->getEmail());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidProfiles(): iterable
    {
        yield 'name shorter than 3 characters' => [['name' => 'ab'], 'name'];
        yield 'blank email' => [['email' => ''], 'email'];
        yield 'not an email' => [['email' => 'not-an-email'], 'email'];
        yield 'email of another user' => [['email' => UserFixtures::OTHER_USER_EMAIL], 'email'];
        yield 'salary is not a number' => [['salary' => 'a lot'], 'salary'];
        yield 'unknown tax profile' => [['taxProfile' => 'vat_20'], 'taxProfile'];
    }

    /**
     * What the profile form sends.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function profile(array $overrides = []): array
    {
        return array_replace([
            'name'       => 'New Name',
            'email'      => 'new@example.com',
            'salary'     => '150000.00',
            'taxProfile' => TaxProfile::Ndfl15->value,
            'password'   => '',
        ], $overrides);
    }

    private function passwordHasher(): UserPasswordHasherInterface
    {
        return static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    private function logIn(bool $rememberMe, string $password = UserFixtures::PASSWORD): void
    {
        $this->postJson('/api/login', [
            'username'    => UserFixtures::ADMIN_EMAIL,
            'password'    => $password,
            'remember_me' => $rememberMe,
        ]);
    }

    private function rememberMeCookie(): ?Cookie
    {
        return $this->client->getCookieJar()->get(self::REMEMBER_ME_COOKIE);
    }

    /**
     * Drops every other cookie, the session included: a new browser session or a session
     * removed by the server's garbage collector.
     */
    private function continueWithOnly(?Cookie $cookie): void
    {
        self::assertNotNull($cookie, 'Expected a remember-me cookie.');

        $cookieJar = $this->client->getCookieJar();
        $cookieJar->clear();
        $cookieJar->set($cookie);
    }
}
