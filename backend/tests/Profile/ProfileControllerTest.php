<?php

namespace App\Tests\Profile;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserGame;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    public function testGetProfileReturnsUserData(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create a test user
        $username = 'test_user_' . bin2hex(random_bytes(6));
        $password = 'TestPassword123!';

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        // Create a game for the user
        $game = new Game();
        $game->setCreatedBy($user);
        $game->setTotalScore(85);
        $game->setTotalQuestions(10);
        $game->setStartedAt(new \DateTimeImmutable());
        $game->setCompletedAt(new \DateTimeImmutable());
        $game->setSaved(true);
        $game->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($game);
        $entityManager->flush();

        // Create UserGame association
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);
        $userGame->setRole('owner');

        $entityManager->persist($userGame);
        $entityManager->flush();
        $entityManager->clear();

        // Fetch user and create token
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);
        $userId = (string) $user->getId();
        $token = $jwtManager->create($user);
        $client->request(
            'GET',
            '/api/user/' . $userId,
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

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('games', $data);
        $this->assertSame($username, $data['user']['username']);
        $this->assertArrayHasKey('id', $data['user']);
        $this->assertArrayHasKey('createdAt', $data['user']);
        $this->assertArrayHasKey('updatedAt', $data['user']);
        
        // Assert game is included
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
        $client = static::createClient();

        $client->request(
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
        $client = static::createClient();

        $client->request(
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
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create first user with a game
        $username1 = 'user_' . bin2hex(random_bytes(6));
        $password1 = 'TestPassword123!';

        $user1 = new User();
        $user1->setUsername($username1);
        $user1->setPassword($passwordHasher->hashPassword($user1, $password1));
        $user1->setCreatedAt(new \DateTimeImmutable());
        $user1->setUpdatedAt(new \DateTimeImmutable());

        // Create second user without games
        $username2 = 'user_' . bin2hex(random_bytes(6));
        $password2 = 'TestPassword456!';

        $user2 = new User();
        $user2->setUsername($username2);
        $user2->setPassword($passwordHasher->hashPassword($user2, $password2));
        $user2->setCreatedAt(new \DateTimeImmutable());
        $user2->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user1);
        $entityManager->persist($user2);
        $entityManager->flush();

        // Create a game for user1 only
        $game = new Game();
        $game->setCreatedBy($user1);
        $game->setTotalScore(75);
        $game->setTotalQuestions(10);
        $game->setStartedAt(new \DateTimeImmutable());
        $game->setSaved(true);
        $game->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($game);
        $entityManager->flush();

        // Create UserGame association for user1
        $userGame = new UserGame();
        $userGame->setUser($user1);
        $userGame->setGame($game);
        $userGame->setRole('owner');

        $entityManager->persist($userGame);
        $entityManager->flush();
        $entityManager->clear();

        // Get tokens for both users
        $user1 = $entityManager->getRepository(User::class)->findOneByUsername($username1);
        $user1Id = (string) $user1->getId();
        $token1 = $jwtManager->create($user1);

        $user2 = $entityManager->getRepository(User::class)->findOneByUsername($username2);
        $user2Id = (string) $user2->getId();
        $token2 = $jwtManager->create($user2);

        // User1 should see their own profile with their game
        $client->request(
            'GET',
            '/api/user/' . $user1Id,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1,
            ]
        );

        $this->assertResponseIsSuccessful();
        $data1 = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertSame($username1, $data1['user']['username']);
        $this->assertCount(1, $data1['games']);

        // User2 should see their own profile with no games
        $client->request(
            'GET',
            '/api/user/' . $user2Id,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2,
            ]
        );

        $this->assertResponseIsSuccessful();
        $data2 = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertSame($username2, $data2['user']['username']);
        $this->assertCount(0, $data2['games']);
        
        // Verify user2 cannot see user1's game
        $this->assertNotEquals($data1['user']['id'], $data2['user']['id']);
    }
}