<?php

namespace App\Tests\Unit\Service;

use App\Exception\ConflictException;
use App\Exception\ValidationException;
use App\Service\UserService;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UserServiceTest extends KernelTestCase
{
    use Factories;

    private UserService $userService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userService = static::getContainer()->get(UserService::class);
    }

    public function testGetUserProfileReturnsData(): void
    {
        $user = UserFactory::createOne();
        $profile = $this->userService->getUserProfile($user->_real());

        $this->assertArrayHasKey('user', $profile);
        $this->assertArrayHasKey('games', $profile);
        $this->assertSame($user->getUsername(), $profile['user']['username']);
    }

    public function testUpdateUsernameSucceeds(): void
    {
        $user = UserFactory::createOne();
        $updated = $this->userService->updateUsername($user->_real(), 'newname');
        $this->assertSame('newname', $updated->getUsername());
    }

    public function testUpdateUsernameThrowsOnEmpty(): void
    {
        $user = UserFactory::createOne();
        $this->expectException(ValidationException::class);
        $this->userService->updateUsername($user->_real(), '');
    }

    public function testUpdateUsernameThrowsOnDuplicate(): void
    {
        UserFactory::createOne(['username' => 'existing']);
        $user = UserFactory::createOne();

        $this->expectException(ConflictException::class);
        $this->userService->updateUsername($user->_real(), 'existing');
    }

    public function testUpdatePasswordSucceeds(): void
    {
        $user = UserFactory::createOne();
        $this->userService->updatePassword($user->_real(), 'Str0ngP@ssw0rd!', 'N3wStr0ng@Pass');
        // No exception = success
        $this->assertTrue(true);
    }

    public function testUpdatePasswordThrowsOnWrongCurrent(): void
    {
        $user = UserFactory::createOne();
        $this->expectException(ValidationException::class);
        $this->userService->updatePassword($user->_real(), 'wrongpassword', 'N3wStr0ng@Pass');
    }

    public function testUpdatePasswordThrowsOnWeakNew(): void
    {
        $user = UserFactory::createOne();
        $this->expectException(ValidationException::class);
        $this->userService->updatePassword($user->_real(), 'Str0ngP@ssw0rd!', 'weak');
    }
}
