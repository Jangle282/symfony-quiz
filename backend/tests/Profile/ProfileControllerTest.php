<?php

namespace App\Tests\Profile;

use App\Tests\ApiTestCase;
use App\Tests\Factory\GameFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Factory\UserGameFactory;
use App\Tests\Trait\AuthenticatesUsers;

class ProfileControllerTest extends ApiTestCase
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
            'totalScore' => 85,
            'totalQuestions' => 10,
            'completedAt' => new \DateTimeImmutable(),
            'saved' => true,
        ]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => 'owner']);

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
        $this->assertSame('owner', $returnedGame['role']);
        $this->assertArrayHasKey('joinedAt', $returnedGame);
        $this->assertArrayHasKey('totalScore', $returnedGame);
        $this->assertSame(85, $returnedGame['totalScore']);
        $this->assertArrayHasKey('totalQuestions', $returnedGame);
        $this->assertSame(10, $returnedGame['totalQuestions']);
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

        $game = GameFactory::createOne(['createdBy' => $user1, 'saved' => true]);
        UserGameFactory::createOne(['user' => $user1, 'game' => $game, 'role' => 'owner']);

        $token1 = $this->generateToken($user1);
        $token2 = $this->generateToken($user2);

        $this->client->request(
            'GET',
            '/api/user/' . $user1->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1,
            ]
        );

        $this->assertResponseIsSuccessful();
        $data1 = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertSame($user1->getUsername(), $data1['user']['username']);
        $this->assertCount(1, $data1['games']);

        $this->client->request(
            'GET',
            '/api/user/' . $user2->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2,
            ]
        );

        $this->assertResponseIsSuccessful();
        $data2 = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertSame($user2->getUsername(), $data2['user']['username']);
        $this->assertCount(0, $data2['games']);
        
        $this->assertNotEquals($data1['user']['id'], $data2['user']['id']);
    }
}