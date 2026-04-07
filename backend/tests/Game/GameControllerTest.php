<?php

namespace App\Tests\Game;

use App\Entity\Game;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    public function testDeleteGameSucceedsForOwner(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create a user
        $username = 'game_owner_' . bin2hex(random_bytes(6));
        $password = 'OwnP@ssw0rd123!';

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        // Create a game owned by the user
        $game = new Game();
        $game->setCreatedBy($user);
        $game->setTotalScore(100);
        $game->setTotalQuestions(10);
        $game->setStartedAt(new \DateTimeImmutable());
        $game->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($game);
        $entityManager->flush();

        $gameId = $game->getId();
        $entityManager->clear();

        // Generate JWT token for the user
        $user = $entityManager->find(User::class, $user->getId());
        $token = $jwtManager->create($user);

        // Make delete request with JWT token
        $client->request(
            'DELETE',
            '/api/games/' . $gameId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(204);

        // Verify game was deleted
        $deletedGame = $entityManager->find(Game::class, $gameId);
        $this->assertNull($deletedGame);
    }

    public function testDeleteGameFailsForUnauthorizedUser(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create two users
        $ownerUsername = 'owner_' . bin2hex(random_bytes(6));
        $ownerPassword = 'Own@ssword123!';

        $owner = new User();
        $owner->setUsername($ownerUsername);
        $owner->setPassword($passwordHasher->hashPassword($owner, $ownerPassword));
        $owner->setCreatedAt(new \DateTimeImmutable());
        $owner->setUpdatedAt(new \DateTimeImmutable());

        $otherUsername = 'other_' . bin2hex(random_bytes(6));
        $otherPassword = 'Oth@ssword123!';

        $other = new User();
        $other->setUsername($otherUsername);
        $other->setPassword($passwordHasher->hashPassword($other, $otherPassword));
        $other->setCreatedAt(new \DateTimeImmutable());
        $other->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($owner);
        $entityManager->persist($other);
        $entityManager->flush();

        // Create a game owned by owner
        $game = new Game();
        $game->setCreatedBy($owner);
        $game->setTotalScore(50);
        $game->setTotalQuestions(5);
        $game->setStartedAt(new \DateTimeImmutable());
        $game->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($game);
        $entityManager->flush();

        $gameId = $game->getId();
        $entityManager->clear();

        // Generate JWT token for other user
        $other = $entityManager->find(User::class, $other->getId());
        $token = $jwtManager->create($other);

        // Try to delete game with unauthorized user
        $client->request(
            'DELETE',
            '/api/games/' . $gameId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(403);

        // Verify game still exists
        $game = $entityManager->find(Game::class, $gameId);
        $this->assertNotNull($game);
    }

    public function testDeleteGameFailsWithoutAuthentication(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');

        // Create a user and game
        $username = 'user_' . bin2hex(random_bytes(6));
        $password = 'Pass@word123!';

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        $game = new Game();
        $game->setCreatedBy($user);
        $game->setTotalScore(75);
        $game->setTotalQuestions(7);
        $game->setStartedAt(new \DateTimeImmutable());
        $game->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($game);
        $entityManager->flush();

        $gameId = $game->getId();

        // Try to delete game without authentication
        $client->request(
            'DELETE',
            '/api/games/' . $gameId,
            [],
            [],
            []
        );

        $this->assertResponseStatusCodeSame(401);

        // Verify game still exists
        $game = $entityManager->find(Game::class, $gameId);
        $this->assertNotNull($game);
    }
}
