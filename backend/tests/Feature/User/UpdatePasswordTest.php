<?php

namespace App\Tests\Feature\User;

use App\Entity\User;
use App\Tests\ApiTestCase;
use App\Tests\Factory\UserFactory;
use App\Tests\Trait\AuthenticatesUsers;

class UpdatePasswordTest extends ApiTestCase
{
    use AuthenticatesUsers;

    public function setUp(): void
    {
        parent::setUp();
    }

    private function createUserAndToken(): array
    {
        $user = UserFactory::createOne();
        $token = $this->generateToken($user);

        return [$user, $token];
    }

    public function testUpdatePasswordWithValidData(): void
    {
        [$user, $token] = $this->createUserAndToken();

        $this->client->request(
            'PATCH',
            '/api/user/' . $user->getId() . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => UserFactory::defaultPassword(),
                'new_password' => 'NewPassword456@',
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('successfully', $data['message']);
    }

    public function testUpdatePasswordWithoutToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/user/00000000-0000-0000-0000-000000000000/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'current_password' => UserFactory::defaultPassword(),
                'new_password' => 'NewPassword456@',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdatePasswordWithInvalidCurrentPassword(): void
    {       
        [$user, $token] = $this->createUserAndToken();

        $this->client->request(
            'PATCH',
            '/api/user/' . $user->getId() . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => 'WrongPassword123!',
                'new_password' => 'NewPassword456@',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Current password', $data['error']);
    }

    public function testUpdatePasswordWithTooShortPassword(): void
    {
        [$user, $token] = $this->createUserAndToken();

        $this->client->request(
            'PATCH',
            '/api/user/' . $user->getId() . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => UserFactory::defaultPassword(),
                'new_password' => 'Short1@',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('at least 10 characters', $data['error']);
    }

    public function testUpdatePasswordWithoutNumbers(): void
    {
        [$user, $token] = $this->createUserAndToken();

        $this->client->request(
            'PATCH',
            '/api/user/' . $user->getId() . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => UserFactory::defaultPassword(),
                'new_password' => 'NoNumbers@NoNum',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testUpdatePasswordWithoutSymbols(): void
    {
        [$user, $token] = $this->createUserAndToken();

        $this->client->request(
            'PATCH',
            '/api/user/' . $user->getId() . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => UserFactory::defaultPassword(),
                'new_password' => 'NoSymbols1234',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testUserCanOnlyChangeTheirOwnPassword(): void
    {
        $originalPassword = 'Original@Pass123';
        $newPassword = 'NewPassword456@';

        $user1 = UserFactory::createOne(['password' => $originalPassword]);
        $user2 = UserFactory::createOne(['password' => $originalPassword]);

        $token1 = $this->generateToken($user1);

        $this->client->request(
            'PATCH',
            '/api/user/' . $user1->getId() . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1,
            ],
            json_encode([
                'current_password' => $originalPassword,
                'new_password' => $newPassword,
            ])
        );

        $this->assertResponseIsSuccessful();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $passwordHasher = self::getContainer()->get('security.password_hasher');
        $entityManager->clear();

        $user1Updated = $entityManager->getRepository(User::class)->find($user1->getId());
        $user2Updated = $entityManager->getRepository(User::class)->find($user2->getId());

        $this->assertTrue($passwordHasher->isPasswordValid($user1Updated, $newPassword), 'User1 password should be changed');
        $this->assertTrue($passwordHasher->isPasswordValid($user2Updated, $originalPassword), 'User2 password should remain unchanged');
    }
}
