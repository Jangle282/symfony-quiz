<?php

namespace App\Tests\Feature\Game;

use App\Entity\Answer;
use App\Tests\ApiTestCase;
use App\Tests\Factory\AnswerFactory;
use App\Tests\Factory\CategoryFactory;
use App\Tests\Factory\DifficultyFactory;
use App\Tests\Factory\GameFactory;
use App\Tests\Factory\QuestionFactory;
use App\Tests\Factory\RoundFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Factory\UserGameFactory;

class SelectAnswerTest extends ApiTestCase
{
    private function createGameWithQuestion(object $user): array
    {
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $category = CategoryFactory::createOne(['name' => 'General Knowledge']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty, 'totalScore' => 0]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => 'host']);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);
        $question = QuestionFactory::createOne(['round' => $round, 'questionText' => 'What is 1+1?']);
        $correctAnswer = AnswerFactory::createOne(['question' => $question, 'answerText' => '2', 'isCorrect' => true]);
        $wrongAnswer = AnswerFactory::createOne(['question' => $question, 'answerText' => '3', 'isCorrect' => false]);

        return [
            'game' => $game,
            'round' => $round,
            'question' => $question,
            'correctAnswer' => $correctAnswer,
            'wrongAnswer' => $wrongAnswer,
        ];
    }

    public function testSelectAnswerSucceeds(): void
    {
        $user = UserFactory::createOne();
        $data = $this->createGameWithQuestion($user);

        $token = $this->generateToken($user);

        $url = sprintf(
            '/api/games/%s/rounds/%s/questions/%s/answers/%s/select',
            $data['game']->getId(),
            $data['round']->getId(),
            $data['question']->getId(),
            $data['correctAnswer']->getId()
        );

        $this->client->request('POST', $url, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Answer selected.', $response['message']);
        $this->assertSame($data['correctAnswer']->getId()->toString(), $response['selected_answer_id']);

        // Verify in database
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $answer = $em->find(Answer::class, $data['correctAnswer']->getId());
        $this->assertTrue($answer->isUserSelected());
    }

    public function testSelectAnswerDeselectsPreviousAnswer(): void
    {
        $user = UserFactory::createOne();
        $data = $this->createGameWithQuestion($user);

        $token = $this->generateToken($user);

        // First select the correct answer
        $url = sprintf(
            '/api/games/%s/rounds/%s/questions/%s/answers/%s/select',
            $data['game']->getId(),
            $data['round']->getId(),
            $data['question']->getId(),
            $data['correctAnswer']->getId()
        );

        $this->client->request('POST', $url, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseStatusCodeSame(200);

        // Now select the wrong answer
        $url = sprintf(
            '/api/games/%s/rounds/%s/questions/%s/answers/%s/select',
            $data['game']->getId(),
            $data['round']->getId(),
            $data['question']->getId(),
            $data['wrongAnswer']->getId()
        );

        $this->client->request('POST', $url, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseStatusCodeSame(200);

        // Verify the correct answer is no longer selected
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $correctAnswer = $em->find(Answer::class, $data['correctAnswer']->getId());
        $wrongAnswer = $em->find(Answer::class, $data['wrongAnswer']->getId());
        $this->assertFalse($correctAnswer->isUserSelected());
        $this->assertTrue($wrongAnswer->isUserSelected());
    }

    public function testSelectAnswerFailsForCompletedGame(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'hard']);
        $category = CategoryFactory::createOne(['name' => 'Trivia']);
        $game = GameFactory::createOne([
            'createdBy' => $user,
            'difficulty' => $difficulty,
            'completedAt' => new \DateTimeImmutable(),
        ]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => 'host']);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category]);
        $question = QuestionFactory::createOne(['round' => $round]);
        $answer = AnswerFactory::createOne(['question' => $question, 'isCorrect' => true]);

        $token = $this->generateToken($user);

        $url = sprintf(
            '/api/games/%s/rounds/%s/questions/%s/answers/%s/select',
            $game->getId(),
            $round->getId(),
            $question->getId(),
            $answer->getId()
        );

        $this->client->request('POST', $url, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Game is already completed.', $response['error']);
    }

    public function testSelectAnswerFailsForNonParticipant(): void
    {
        $owner = UserFactory::createOne();
        $other = UserFactory::createOne();
        $data = $this->createGameWithQuestion($owner);

        $token = $this->generateToken($other);

        $url = sprintf(
            '/api/games/%s/rounds/%s/questions/%s/answers/%s/select',
            $data['game']->getId(),
            $data['round']->getId(),
            $data['question']->getId(),
            $data['correctAnswer']->getId()
        );

        $this->client->request('POST', $url, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testSelectAnswerFailsForWrongQuestion(): void
    {
        $user = UserFactory::createOne();
        $data = $this->createGameWithQuestion($user);

        // Create another question in the same round
        $otherQuestion = QuestionFactory::createOne(['round' => $data['round'], 'questionText' => 'Other?']);
        $otherAnswer = AnswerFactory::createOne(['question' => $otherQuestion, 'isCorrect' => true]);

        $token = $this->generateToken($user);

        // Try to select an answer from otherQuestion but pass data['question'] as the question
        $url = sprintf(
            '/api/games/%s/rounds/%s/questions/%s/answers/%s/select',
            $data['game']->getId(),
            $data['round']->getId(),
            $data['question']->getId(),
            $otherAnswer->getId() // This answer belongs to otherQuestion, not data['question']
        );

        $this->client->request('POST', $url, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testSelectAnswerRequiresAuthentication(): void
    {
        $user = UserFactory::createOne();
        $data = $this->createGameWithQuestion($user);

        $url = sprintf(
            '/api/games/%s/rounds/%s/questions/%s/answers/%s/select',
            $data['game']->getId(),
            $data['round']->getId(),
            $data['question']->getId(),
            $data['correctAnswer']->getId()
        );

        $this->client->request('POST', $url);

        $this->assertResponseStatusCodeSame(401);
    }
}
