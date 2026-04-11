<?php

namespace App\Tests\Feature\Game;

use App\Entity\Game;
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

class CompleteGameTest extends ApiTestCase
{
    public function testCompleteGameSucceeds(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $category = CategoryFactory::createOne(['name' => 'General Knowledge']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);

        // Question 1: correct answer selected
        $q1 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Q1?']);
        AnswerFactory::createOne(['question' => $q1, 'answerText' => 'Right', 'isCorrect' => true, 'userSelected' => true]);
        AnswerFactory::createOne(['question' => $q1, 'answerText' => 'Wrong', 'isCorrect' => false]);

        // Question 2: wrong answer selected
        $q2 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Q2?']);
        AnswerFactory::createOne(['question' => $q2, 'answerText' => 'Right', 'isCorrect' => true]);
        AnswerFactory::createOne(['question' => $q2, 'answerText' => 'Wrong', 'isCorrect' => false, 'userSelected' => true]);

        // Question 3: correct answer selected
        $q3 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Q3?']);
        AnswerFactory::createOne(['question' => $q3, 'answerText' => 'Right', 'isCorrect' => true, 'userSelected' => true]);
        AnswerFactory::createOne(['question' => $q3, 'answerText' => 'Wrong', 'isCorrect' => false]);

        $token = $this->generateToken($user);

        $this->client->request(
            'POST',
            '/api/games/' . $game->getId() . '/complete',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Game completed.', $response['message']);
        $this->assertSame(2, $response['total_score']);
        $this->assertNotNull($response['completed_at']);

        // Verify in database
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $updatedGame = $em->find(Game::class, $game->getId());
        $this->assertNotNull($updatedGame->getCompletedAt());
    }

    public function testCompleteGameFailsWhenAlreadyCompleted(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'easy']);
        $game = GameFactory::createOne([
            'createdBy' => $user,
            'difficulty' => $difficulty,
            'completedAt' => new \DateTimeImmutable(),
        ]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $token = $this->generateToken($user);

        $this->client->request(
            'POST',
            '/api/games/' . $game->getId() . '/complete',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Game is already completed.', $response['error']);
    }

    public function testCompleteGameDeniesNonParticipant(): void
    {
        $owner = UserFactory::createOne();
        $other = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $game = GameFactory::createOne(['createdBy' => $owner, 'difficulty' => $difficulty]);
        UserGameFactory::createOne(['user' => $owner, 'game' => $game, 'role' => UserGameRole::Host]);

        $token = $this->generateToken($other);

        $this->client->request(
            'POST',
            '/api/games/' . $game->getId() . '/complete',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCompleteGameRequiresAuthentication(): void
    {
        $game = GameFactory::createOne();

        $this->client->request(
            'POST',
            '/api/games/' . $game->getId() . '/complete'
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
