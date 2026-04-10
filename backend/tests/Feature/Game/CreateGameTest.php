<?php

namespace App\Tests\Feature\Game;

use App\DTO\AnswerDTO;
use App\DTO\QuestionDTO;
use App\Entity\Answer;
use App\Entity\Game;
use App\Entity\Question;
use App\Entity\Round;
use App\Entity\UserGame;
use App\Service\QuestionProviderInterface;
use App\Tests\ApiTestCase;
use App\Tests\Factory\CategoryFactory;
use App\Tests\Factory\DifficultyFactory;
use App\Tests\Factory\UserFactory;

class CreateGameTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    private function seedReferenceData(): void
    {
        CategoryFactory::createOne(['name' => 'General Knowledge']);
        DifficultyFactory::createOne(['name' => 'easy']);
        DifficultyFactory::createOne(['name' => 'medium']);
        DifficultyFactory::createOne(['name' => 'hard']);
    }

    private function mockQuestionProvider(): void
    {
        $questions = [];
        for ($i = 1; $i <= 5; $i++) {
            $questions[] = new QuestionDTO(
                questionText: "Question $i: What is the answer?",
                answers: [
                    new AnswerDTO(answerText: "Correct Answer $i", isCorrect: true),
                    new AnswerDTO(answerText: "Wrong Answer A$i", isCorrect: false),
                    new AnswerDTO(answerText: "Wrong Answer B$i", isCorrect: false),
                    new AnswerDTO(answerText: "Wrong Answer C$i", isCorrect: false),
                ],
            );
        }

        $mock = $this->createMock(QuestionProviderInterface::class);
        $mock->method('fetchQuestions')->willReturn($questions);

        self::getContainer()->set(QuestionProviderInterface::class, $mock);
    }

    public function testCreateGameSucceeds(): void
    {
        $this->mockQuestionProvider();
        $user = UserFactory::createOne();
        $token = $this->generateToken($user);

        $this->client->request(
            'POST',
            '/api/games',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['difficulty' => 'easy', 'name' => 'My Quiz'])
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertSame('My Quiz', $response['name']);
        $this->assertSame('easy', $response['difficulty']);
        $this->assertArrayHasKey('round', $response);
        $this->assertSame(1, $response['round']['round_number']);
        $this->assertSame('General Knowledge', $response['round']['category']);
        $this->assertArrayHasKey('first_question', $response);
        $this->assertNotNull($response['first_question']);
        $this->assertArrayHasKey('question_text', $response['first_question']);
        $this->assertCount(4, $response['first_question']['answers']);

        // Verify correct answer is NOT exposed in the response
        foreach ($response['first_question']['answers'] as $answer) {
            $this->assertArrayNotHasKey('is_correct', $answer);
        }

        // Verify entities were persisted
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $game = $em->find(Game::class, $response['id']);
        $this->assertNotNull($game);
        $this->assertSame('My Quiz', $game->getName());
        $this->assertSame(0, $game->getTotalScore());
        $this->assertNull($game->getCompletedAt());

        // Verify UserGame was created with host role
        $userGames = $em->getRepository(UserGame::class)->findBy(['game' => $game]);
        $this->assertCount(1, $userGames);
        $this->assertSame('host', $userGames[0]->getRole());

        // Verify round and questions
        $rounds = $em->getRepository(Round::class)->findBy(['game' => $game]);
        $this->assertCount(1, $rounds);

        $questions = $em->getRepository(Question::class)->findBy(['round' => $rounds[0]]);
        $this->assertCount(5, $questions);

        $answers = $em->getRepository(Answer::class)->findBy(['question' => $questions[0]]);
        $this->assertCount(4, $answers);
    }

    public function testCreateGameDefaultsToMediumDifficulty(): void
    {
        $this->mockQuestionProvider();
        $user = UserFactory::createOne();
        $token = $this->generateToken($user);

        $this->client->request(
            'POST',
            '/api/games',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('medium', $response['difficulty']);
        $this->assertNull($response['name']);
    }

    public function testCreateGameRejectsInvalidDifficulty(): void
    {
        $user = UserFactory::createOne();
        $token = $this->generateToken($user);

        $this->client->request(
            'POST',
            '/api/games',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['difficulty' => 'impossible'])
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Invalid difficulty', $response['error']);
    }

    public function testCreateGameRequiresAuthentication(): void
    {
        $this->client->request(
            'POST',
            '/api/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['difficulty' => 'easy'])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
