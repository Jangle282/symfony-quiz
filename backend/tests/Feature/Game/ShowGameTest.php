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

class ShowGameTest extends ApiTestCase
{
    public function testShowGameSucceeds(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $category = CategoryFactory::createOne(['name' => 'General Knowledge']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => 'host']);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);
        $question1 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Question 1?']);
        AnswerFactory::createOne(['question' => $question1, 'answerText' => 'A1', 'isCorrect' => true]);
        AnswerFactory::createOne(['question' => $question1, 'answerText' => 'A2', 'isCorrect' => false]);

        $question2 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Question 2?']);
        AnswerFactory::createOne(['question' => $question2, 'answerText' => 'B1', 'isCorrect' => true]);
        AnswerFactory::createOne(['question' => $question2, 'answerText' => 'B2', 'isCorrect' => false]);

        $token = $this->generateToken($user);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($game->getId()->toString(), $response['id']);
        $this->assertSame('medium', $response['difficulty']);
        $this->assertCount(1, $response['rounds']);
        $this->assertSame(2, $response['rounds'][0]['total_questions']);
        $this->assertSame(0, $response['rounds'][0]['answered_questions']);
        $this->assertNotNull($response['current_question']);
    }

    public function testShowGameDeniesNonParticipant(): void
    {
        $owner = UserFactory::createOne();
        $other = UserFactory::createOne();
        $game = GameFactory::createOne(['createdBy' => $owner]);
        UserGameFactory::createOne(['user' => $owner, 'game' => $game, 'role' => 'host']);

        $token = $this->generateToken($other);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testShowGameRequiresAuthentication(): void
    {
        $game = GameFactory::createOne();

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId()
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowGameReturnsCorrectProgress(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'easy']);
        $category = CategoryFactory::createOne(['name' => 'Science']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => 'host']);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);
        $question1 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Q1?']);
        AnswerFactory::createOne(['question' => $question1, 'answerText' => 'A', 'isCorrect' => true, 'userSelected' => true]);
        AnswerFactory::createOne(['question' => $question1, 'answerText' => 'B', 'isCorrect' => false]);

        $question2 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Q2?']);
        AnswerFactory::createOne(['question' => $question2, 'answerText' => 'C', 'isCorrect' => true]);
        AnswerFactory::createOne(['question' => $question2, 'answerText' => 'D', 'isCorrect' => false]);

        $token = $this->generateToken($user);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $response['rounds'][0]['answered_questions']);
        $this->assertNotNull($response['current_question']);
        $this->assertSame('Q2?', $response['current_question']['question_text']);
    }
}
