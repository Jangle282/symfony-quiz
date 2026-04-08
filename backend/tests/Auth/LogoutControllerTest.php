<?php

namespace App\Tests\Auth;

use App\Tests\ApiTestCase;
use App\Tests\Factory\UserFactory;
use App\Tests\Trait\AuthenticatesUsers;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LogoutControllerTest extends ApiTestCase
{
    use AuthenticatesUsers;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testLogoutReturnsNoContentForAuthenticatedUser(): void
    {
        $user = UserFactory::createOne();
        $token = $this->generateToken($user);

        $this->client->request(
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
        $this->client->request('POST', '/api/logout');

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertArrayNotHasKey('token', $data);
        $this->assertArrayNotHasKey('refresh_token', $data);
    }
}
