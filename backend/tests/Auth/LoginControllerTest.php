<?php

namespace App\Tests\Auth;

use App\Tests\ApiTestCase;
use App\Tests\Factory\UserFactory;

class LoginControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }
    public function testLoginReturnsTokenWithValidCredentials(): void
    {
        $user = UserFactory::createOne(['username' => 'login_user_' . bin2hex(random_bytes(6))]);

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'username' => $user->getUsername(),
                'password' => UserFactory::defaultPassword(),
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame($user->getUsername(), $data['user']['username']);
    }

    public function testLoginReturnsUnauthorizedWithInvalidCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'username' => 'nonexistent_user',
                'password' => 'wrongPassword123!',
            ])
        );

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertSame('Invalid credentials.', $data['error']);
    }

    public function testLoginWithUserCreatedDirectlyInDatabase(): void
    {
        $user = UserFactory::createOne(['username' => 'db_user_' . bin2hex(random_bytes(6))]);

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'username' => $user->getUsername(),
                'password' => UserFactory::defaultPassword(),
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame($user->getUsername(), $data['user']['username']);
    }

    public function testLoginThrottlesAfterTooManyAttempts(): void
    {
        $this->consumeRateLimiter('api_login', 5);

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'username' => 'any_user',
                'password' => 'anyPassword123!',
            ])
        );

        $this->assertResponseStatusCodeSame(429);

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertSame('Too many login attempts, please try again later.', $data['error']);
    }
}
