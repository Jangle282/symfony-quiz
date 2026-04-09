<?php

namespace App\Tests\Feature\User;

use App\Tests\ApiTestCase;
use App\Tests\Factory\UserFactory;
use App\Tests\Trait\AuthenticatesUsers;

class UpdateUsernameTest extends ApiTestCase
{
    use AuthenticatesUsers;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testUpdateUsernameWithValidData(): void
    {
        $user = UserFactory::createOne();
        $token = $this->generateToken($user);

        $newUsername = 'new_username_' . bin2hex(random_bytes(6));

        $this->client->request(
            'PATCH',
            '/api/user/' . $user->getId() . '/username',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'username' => $newUsername,
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame($newUsername, $data['user']['username']);
    }

    public function testUpdateUsernameWithoutToken(): void
    {
        $newUsername = 'new_username_' . bin2hex(random_bytes(6));

        $this->client->request(
            'PATCH',
            '/api/user/00000000-0000-0000-0000-000000000000/username',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'username' => $newUsername,
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateUsernameWithEmptyUsername(): void
    {
        $user = UserFactory::createOne();
        $token = $this->generateToken($user);

        $this->client->request(
            'PATCH',
            '/api/user/' . $user->getId() . '/username',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'username' => '',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testUpdateUsernameWithExistingUsername(): void
    {
        $existingUser = UserFactory::createOne();
        $user = UserFactory::createOne();

        $token = $this->generateToken($user);

        $this->client->request(
            'PATCH',
            '/api/user/' . $user->getId() . '/username',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'username' => $existingUser->getUsername(),
            ])
        );

        $this->assertResponseStatusCodeSame(409);
        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
    }
}
