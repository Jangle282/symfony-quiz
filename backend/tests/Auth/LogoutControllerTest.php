<?php

namespace App\Tests\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LogoutControllerTest extends WebTestCase
{
    public function testLogoutReturnsNoContentForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $creds = $this->registerAndLogin($client);
        $token = $creds['token'];

        $client->request(
            'POST',
            '/api/logout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(204);
    }

    public function testLogoutReturnsUnauthorizedWithoutToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/logout');

        $this->assertResponseStatusCodeSame(401);
    }

    private function registerAndLogin($client): string
    {
        $username = 'user_' . bin2hex(random_bytes(6));
        $password = 'Str0ngP@ssw0rd!';

        $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['username' => $username, 'password' => $password])
        );
        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['username' => $username, 'password' => $password])
        );
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        return ['token' => $data['token'], 'refresh_token' => $data['refresh_token']];
    }
}
