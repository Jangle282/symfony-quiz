<?php

namespace App\Tests\Auth;

use App\Tests\Factory\RefreshTokenFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RefreshControllerTest extends WebTestCase
{
    public function testRefreshReturnsNewTokenForAuthenticatedUser(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $refreshToken = RefreshTokenFactory::createOne(['user' => $user]);

        $client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['refresh_token' => $refreshToken->getToken()])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertIsString($data['token']);
        $this->assertIsString($data['refresh_token']);
    }

    public function testRefreshReturnsBadRequestWithoutToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/token/refresh');

        $this->assertResponseStatusCodeSame(400);
    }
}
