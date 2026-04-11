<?php

namespace App\Tests\Unit\Service;

use App\Exception\ConflictException;
use App\Exception\ValidationException;
use App\Service\AuthService;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Zenstruck\Foundry\Test\Factories;

class AuthServiceTest extends KernelTestCase
{
    use Factories;

    private AuthService $authService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->authService = static::getContainer()->get(AuthService::class);
    }

    public function testRegisterCreatesUser(): void
    {
        $user = $this->authService->register('newuser', 'Str0ngP@ss!');
        $this->assertSame('newuser', $user->getUsername());
        $this->assertNotNull($user->getId());
    }

    public function testRegisterThrowsOnEmptyUsername(): void
    {
        $this->expectException(ValidationException::class);
        $this->authService->register('', 'Str0ngP@ss!');
    }

    public function testRegisterThrowsOnWeakPassword(): void
    {
        $this->expectException(ValidationException::class);
        $this->authService->register('testuser', 'weak');
    }

    public function testRegisterThrowsOnDuplicateUsername(): void
    {
        UserFactory::createOne(['username' => 'taken']);
        $this->expectException(ConflictException::class);
        $this->authService->register('taken', 'Str0ngP@ss!');
    }

    public function testLoginReturnsTokens(): void
    {
        $this->authService->register('loginuser', 'Str0ngP@ss!');
        $result = $this->authService->login('loginuser', 'Str0ngP@ss!');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('user', $result);
    }

    public function testLoginThrowsOnInvalidCredentials(): void
    {
        $this->authService->register('loginuser2', 'Str0ngP@ss!');
        $this->expectException(UnauthorizedHttpException::class);
        $this->authService->login('loginuser2', 'wrongpassword');
    }

    public function testRefreshRotatesToken(): void
    {
        $this->authService->register('refreshuser', 'Str0ngP@ss!');
        $loginResult = $this->authService->login('refreshuser', 'Str0ngP@ss!');

        $refreshResult = $this->authService->refresh($loginResult['refresh_token']);
        $this->assertArrayHasKey('token', $refreshResult);
        $this->assertArrayHasKey('refresh_token', $refreshResult);
        $this->assertNotSame($loginResult['refresh_token'], $refreshResult['refresh_token']);
    }

    public function testRefreshThrowsOnEmptyToken(): void
    {
        $this->expectException(ValidationException::class);
        $this->authService->refresh('');
    }

    public function testRefreshThrowsOnInvalidToken(): void
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->authService->refresh('nonexistent-token');
    }

    public function testLogoutRevokesTokens(): void
    {
        $this->authService->register('logoutuser', 'Str0ngP@ss!');
        $loginResult = $this->authService->login('logoutuser', 'Str0ngP@ss!');

        $this->authService->logout($loginResult['user']);

        // Clear identity map so refresh re-fetches from DB
        static::getContainer()->get('doctrine.orm.entity_manager')->clear();

        $this->expectException(UnauthorizedHttpException::class);
        $this->authService->refresh($loginResult['refresh_token']);
    }
}
