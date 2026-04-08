<?php

namespace App\Tests\Auth;

use App\Tests\Factory\UserFactory;
use App\Tests\Trait\AuthenticatesUsers;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LogoutControllerTest extends WebTestCase
{
    use AuthenticatesUsers;

    public function testLogoutReturnsNoContentForAuthenticatedUser(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $token = $this->generateToken($user);

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

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertArrayNotHasKey('token', $data);
        $this->assertArrayNotHasKey('refresh_token', $data);
    }
}
