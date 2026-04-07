<?php

namespace App\Tests\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterControllerTest extends WebTestCase
{
    public function testRegisterCreatesNewUser(): void
    {
        $client = static::createClient();
        $username = 'user_' . bin2hex(random_bytes(6));
        $password = 'Str0ngP@ssw0rd!';

        $client->request(
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

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame($username, $data['username']);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);
    }

    public function testRegisterReturnsConflictForDuplicateUsername(): void
    {
        $client = static::createClient();
        $username = 'duplicate_' . bin2hex(random_bytes(6));
        $password = 'Str0ngP@ssw0rd!';

        $payload = json_encode([
            'username' => $username,
            'password' => $password,
        ]);

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], $payload);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], $payload);
        $this->assertResponseStatusCodeSame(409);

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertSame('Username already exists.', $data['error']);
    }
}
