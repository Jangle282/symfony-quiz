<?php

namespace App\Tests\Feature\Game;

use App\Entity\Game;
use App\Tests\ApiTestCase;
use App\Tests\Factory\GameFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Trait\AuthenticatesUsers;

class DeleteGameTest extends ApiTestCase
{
    use AuthenticatesUsers;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testDeleteGameSucceedsForOwner(): void
    {
        $user = UserFactory::createOne();
        $game = GameFactory::createOne(['createdBy' => $user]);
        $gameId = $game->getId();

        $token = $this->generateToken($user);

        $this->client->request(
            'DELETE',
            '/api/games/' . $gameId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(204);

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $deletedGame = $entityManager->find(Game::class, $gameId);
        $this->assertNull($deletedGame);
    }

    public function testDeleteGameFailsForUnauthorizedUser(): void
    {
        $owner = UserFactory::createOne();
        $other = UserFactory::createOne();
        $game = GameFactory::createOne(['createdBy' => $owner]);
        $gameId = $game->getId();

        $token = $this->generateToken($other);

        $this->client->request(
            'DELETE',
            '/api/games/' . $gameId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(403);

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->assertNotNull($entityManager->find(Game::class, $gameId));
    }

    public function testDeleteGameFailsWithoutAuthentication(): void
    {
        $game = GameFactory::createOne();
        $gameId = $game->getId();

        $this->client->request(
            'DELETE',
            '/api/games/' . $gameId,
            [],
            [],
            []
        );

        $this->assertResponseStatusCodeSame(401);

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->assertNotNull($entityManager->find(Game::class, $gameId));
    }
}
