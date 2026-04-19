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

class GameResultsTest extends ApiTestCase
{
    public function testGetResultsSucceeds(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $category = CategoryFactory::createOne(['name' => 'General Knowledge']);
        $game = GameFactory::createOne([
            'createdBy' => $user,
            'difficulty' => $difficulty,
            'completedAt' => new \DateTimeImmutable(),
        ]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);

        // Q1: correct answer selected
        $q1 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'What is 2+2?']);
        AnswerFactory::createOne(['question' => $q1, 'answerText' => '4', 'isCorrect' => true, 'userSelected' => true]);
        AnswerFactory::createOne(['question' => $q1, 'answerText' => '5', 'isCorrect' => false]);

        // Q2: wrong answer selected
        $q2 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Capital of France?']);
        AnswerFactory::createOne(['question' => $q2, 'answerText' => 'Paris', 'isCorrect' => true]);
        AnswerFactory::createOne(['question' => $q2, 'answerText' => 'London', 'isCorrect' => false, 'userSelected' => true]);

        // Q3: no answer selected
        $q3 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Biggest planet?']);
        AnswerFactory::createOne(['question' => $q3, 'answerText' => 'Jupiter', 'isCorrect' => true]);
        AnswerFactory::createOne(['question' => $q3, 'answerText' => 'Mars', 'isCorrect' => false]);

        $token = $this->generateToken($user);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId() . '/results',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($game->getId()->toString(), $response['game_id']);
        $this->assertSame(1, $response['total_score']);
        $this->assertSame(3, $response['total_questions']);
        $this->assertCount(3, $response['questions']);

        // Verify question breakdown contains the right fields
        foreach ($response['questions'] as $q) {
            $this->assertArrayHasKey('question_id', $q);
            $this->assertArrayHasKey('question_text', $q);
            $this->assertArrayHasKey('correct_answer', $q);
            $this->assertArrayHasKey('selected_answer', $q);
            $this->assertArrayHasKey('is_correct', $q);
        }
    }

    public function testGetResultsDeniesNonParticipant(): void
    {
        $owner = UserFactory::createOne();
        $other = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $game = GameFactory::createOne([
            'createdBy' => $owner,
            'difficulty' => $difficulty,
            'completedAt' => new \DateTimeImmutable(),
        ]);
        UserGameFactory::createOne(['user' => $owner, 'game' => $game, 'role' => UserGameRole::Host]);

        $token = $this->generateToken($other);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId() . '/results',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetResultsRequiresAuthentication(): void
    {
        $game = GameFactory::createOne();

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId() . '/results'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetResultsShowsCorrectBreakdown(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'easy']);
        $category = CategoryFactory::createOne(['name' => 'Science']);
        $game = GameFactory::createOne([
            'createdBy' => $user,
            'difficulty' => $difficulty,
            'completedAt' => new \DateTimeImmutable(),
        ]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);

        $q1 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'What is H2O?']);
        AnswerFactory::createOne(['question' => $q1, 'answerText' => 'Water', 'isCorrect' => true, 'userSelected' => true]);
        AnswerFactory::createOne(['question' => $q1, 'answerText' => 'Salt', 'isCorrect' => false]);

        $token = $this->generateToken($user);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId() . '/results',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['questions']);

        $q = $response['questions'][0];
        $this->assertSame('What is H2O?', $q['question_text']);
        $this->assertSame('Water', $q['correct_answer']);
        $this->assertSame('Water', $q['selected_answer']);
        $this->assertTrue($q['is_correct']);
    }
}
