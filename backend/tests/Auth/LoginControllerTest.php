<?php

namespace App\Tests\Auth;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    public function testLoginReturnsTokenWithValidCredentials(): void
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

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => $password,
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame($username, $data['user']['username']);
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
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');

        $username = 'db_user_' . bin2hex(random_bytes(6));
        $password = 'DbP@ssw0rd123!';

        $user = new User();
        $user->setUsername($username);
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => $password,
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame($username, $data['user']['username']);
    }
}
