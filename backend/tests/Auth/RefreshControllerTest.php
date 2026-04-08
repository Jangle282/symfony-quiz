<?php

namespace App\Tests\Auth;

use App\Tests\ApiTestCase;
use App\Tests\Factory\RefreshTokenFactory;
use App\Tests\Factory\UserFactory;

class RefreshControllerTest extends ApiTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRefreshReturnsNewTokenForAuthenticatedUser(): void
    {
        $user = UserFactory::createOne();
        $refreshToken = RefreshTokenFactory::createOne(['user' => $user]);

        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['refresh_token' => $refreshToken->getToken()])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertIsString($data['token']);
        $this->assertIsString($data['refresh_token']);
    }

    public function testRefreshReturnsBadRequestWithoutToken(): void
    {
        $this->client->request('POST', '/api/token/refresh');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRefreshThrottlesAfterTooManyAttempts(): void
    {
        $this->consumeRateLimiter('api_token_refresh', 10);

        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['refresh_token' => 'any-token'])
        );

        $this->assertResponseStatusCodeSame(429);

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertSame('Too many refresh attempts, please try again later.', $data['error']);
    }
}
