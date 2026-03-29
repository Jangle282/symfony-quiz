<?php

namespace App\Tests\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RefreshControllerTest extends WebTestCase
{
    public function testRefreshReturnsNewTokenForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client);

        $client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertIsString($data['token']);
    }

    public function testRefreshReturnsUnauthorizedWithoutToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/token/refresh');

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
        return $data['token'];
    }
}
