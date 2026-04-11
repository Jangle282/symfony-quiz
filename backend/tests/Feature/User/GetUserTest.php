<?php

namespace App\Tests\Feature\User;

use App\Entity\UserGameRole;
use App\Tests\ApiTestCase;
use App\Tests\Factory\GameFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Factory\UserGameFactory;
use App\Tests\Trait\AuthenticatesUsers;

class GetUserTest extends ApiTestCase
{
    use AuthenticatesUsers;
    public function setUp(): void  
    {
        parent::setUp();
    }

    public function testGetProfileReturnsUserData(): void
    {
        $user = UserFactory::createOne();
        $game = GameFactory::createOne([
            'createdBy' => $user,
            'completedAt' => new \DateTimeImmutable(),
        ]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $token = $this->generateToken($user);

        $this->client->request(
            'GET',
            '/api/user/' . $user->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('games', $data);
        $this->assertSame($user->getUsername(), $data['user']['username']);
        $this->assertArrayHasKey('id', $data['user']);
        $this->assertArrayHasKey('createdAt', $data['user']);
        $this->assertArrayHasKey('updatedAt', $data['user']);
        
        $this->assertIsArray($data['games']);
        $this->assertCount(1, $data['games']);
        
        $returnedGame = $data['games'][0];
        $this->assertArrayHasKey('id', $returnedGame);
        $this->assertArrayHasKey('role', $returnedGame);
        $this->assertSame('host', $returnedGame['role']);
        $this->assertArrayHasKey('joinedAt', $returnedGame);
        $this->assertArrayHasKey('totalScore', $returnedGame);
        $this->assertSame(0, $returnedGame['totalScore']);
        $this->assertArrayHasKey('startedAt', $returnedGame);
        $this->assertArrayHasKey('completedAt', $returnedGame);
    }

    public function testGetProfileReturnsUnauthorizedWithoutToken(): void
    {
        $this->client->request(
            'GET',
            '/api/user/00000000-0000-0000-0000-000000000000',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetProfileReturnsUnauthorizedWithInvalidToken(): void
    {
        $this->client->request(
            'GET',
            '/api/user/00000000-0000-0000-0000-000000000000',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer invalid_token',
            ]
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUsersCannotRetrieveEachOthersProfiles(): void
    {
        $user1 = UserFactory::createOne();
        $user2 = UserFactory::createOne();

        $token1 = $this->generateToken($user1);
        $token2 = $this->generateToken($user2);

        // user2 attempts to access user1's profile — must be denied
        $this->client->request(
            'GET',
            '/api/user/' . $user1->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2,
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        // user1 attempts to access user2's profile — must be denied
        $this->client->request(
            'GET',
            '/api/user/' . $user2->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1,
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }
}