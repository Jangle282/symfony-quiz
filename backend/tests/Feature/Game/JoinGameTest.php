<?php

namespace App\Tests\Feature\Game;

use App\Tests\ApiTestCase;
use App\Tests\Factory\AnswerFactory;
use App\Tests\Factory\CategoryFactory;
use App\Tests\Factory\DifficultyFactory;
use App\Tests\Factory\GameFactory;
use App\Tests\Factory\QuestionFactory;
use App\Tests\Factory\RoundFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Factory\UserGameFactory;
use App\Entity\UserGameRole;

class JoinGameTest extends ApiTestCase
{
    public function testJoinGameSucceeds(): void
    {
        $host = UserFactory::createOne();
        $player = UserFactory::createOne();
        $game = GameFactory::createOne(['createdBy' => $host]);
        UserGameFactory::createOne(['user' => $host, 'game' => $game, 'role' => UserGameRole::Host]);

        $token = $this->generateToken($player);

        $this->client->request(
            'POST',
            '/api/games/' . $game->getId() . '/join',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Successfully joined the game.', $response['message']);
        $this->assertSame($game->getId()->toString(), $response['game_id']);
        $this->assertSame('participant', $response['role']);
    }

    public function testJoinGameFailsWhenAlreadyJoined(): void
    {
        $user = UserFactory::createOne();
        $game = GameFactory::createOne(['createdBy' => $user]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $token = $this->generateToken($user);

        $this->client->request(
            'POST',
            '/api/games/' . $game->getId() . '/join',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(409);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('You have already joined this game.', $response['error']);
    }

    public function testJoinGameRequiresAuthentication(): void
    {
        $game = GameFactory::createOne();

        $this->client->request(
            'POST',
            '/api/games/' . $game->getId() . '/join'
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
