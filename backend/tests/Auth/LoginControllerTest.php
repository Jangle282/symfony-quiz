<?php

namespace App\Tests\Auth;

use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    public function testLoginReturnsTokenWithValidCredentials(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['username' => 'login_user_' . bin2hex(random_bytes(6))]);

        $client->request(
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

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame($user->getUsername(), $data['user']['username']);
    }

    public function testLoginReturnsUnauthorizedWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request(
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

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertSame('Invalid credentials.', $data['error']);
    }

    public function testLoginWithUserCreatedDirectlyInDatabase(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['username' => 'db_user_' . bin2hex(random_bytes(6))]);

        $client->request(
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

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame($user->getUsername(), $data['user']['username']);
    }
}
