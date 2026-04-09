<?php

namespace App\Tests\Feature\Auth;

use App\Tests\ApiTestCase;
use App\Tests\Factory\UserFactory;

class RegisterControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testRegisterCreatesNewUser(): void
    {
        $username = 'user_' . bin2hex(random_bytes(6));
        $password = 'Str0ngP@ssw0rd!';

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => $password,
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame($username, $data['username']);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);
    }

    public function testRegisterReturnsConflictForDuplicateUsername(): void
    {
        $existingUser = UserFactory::createOne();

        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], json_encode([
            'username' => $existingUser->getUsername(),
            'password' => 'Str0ngP@ssw0rd!',
        ]));
        $this->assertResponseStatusCodeSame(409);

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertSame('Username already exists.', $data['error']);
    }
}
