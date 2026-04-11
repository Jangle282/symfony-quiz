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

class PreviousQuestionTest extends ApiTestCase
{
    private function createGameWithQuestions(object $user, int $questionCount = 3): array
    {
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $category = CategoryFactory::createOne(['name' => 'General Knowledge']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);

        $questions = [];
        for ($i = 1; $i <= $questionCount; $i++) {
            $question = QuestionFactory::createOne(['round' => $round, 'questionText' => "Question $i?"]);
            AnswerFactory::createOne(['question' => $question, 'answerText' => "Correct $i", 'isCorrect' => true]);
            AnswerFactory::createOne(['question' => $question, 'answerText' => "Wrong $i", 'isCorrect' => false]);
            $questions[] = $question;
        }

        return ['game' => $game, 'round' => $round, 'questions' => $questions];
    }

    public function testPreviousQuestionReturnsPreviousQuestion(): void
    {
        $user = UserFactory::createOne();
        $data = $this->createGameWithQuestions($user);
        $game = $data['game'];
        $round = $data['round'];

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $orderedQuestions = $em->getRepository(\App\Entity\Question::class)
            ->findBy(['round' => $round->getId()], ['id' => 'ASC']);

        $firstQuestion = $orderedQuestions[0];
        $secondQuestion = $orderedQuestions[1];

        $token = $this->generateToken($user);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId() . '/rounds/' . $round->getId() . '/questions/' . $secondQuestion->getId() . '/previous',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotNull($response['question']);
        $this->assertSame($firstQuestion->getId()->toString(), $response['question']['id']);

        // Verify is_correct is NOT exposed
        foreach ($response['question']['answers'] as $answer) {
            $this->assertArrayNotHasKey('is_correct', $answer);
        }
    }

    public function testPreviousQuestionReturnsNullForFirstQuestion(): void
    {
        $user = UserFactory::createOne();
        $data = $this->createGameWithQuestions($user, 2);
        $game = $data['game'];
        $round = $data['round'];

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $orderedQuestions = $em->getRepository(\App\Entity\Question::class)
            ->findBy(['round' => $round->getId()], ['id' => 'ASC']);

        $firstQuestion = $orderedQuestions[0];

        $token = $this->generateToken($user);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId() . '/rounds/' . $round->getId() . '/questions/' . $firstQuestion->getId() . '/previous',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNull($response['question']);
    }

    public function testPreviousQuestionDeniesNonParticipant(): void
    {
        $owner = UserFactory::createOne();
        $other = UserFactory::createOne();
        $data = $this->createGameWithQuestions($owner);
        $game = $data['game'];
        $round = $data['round'];

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $questions = $em->getRepository(\App\Entity\Question::class)
            ->findBy(['round' => $round->getId()], ['id' => 'ASC']);

        $token = $this->generateToken($other);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId() . '/rounds/' . $round->getId() . '/questions/' . $questions[1]->getId() . '/previous',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPreviousQuestionShowsPreviouslySelectedAnswer(): void
    {
        $user = UserFactory::createOne();
        $data = $this->createGameWithQuestions($user, 3);
        $game = $data['game'];
        $round = $data['round'];

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $orderedQuestions = $em->getRepository(\App\Entity\Question::class)
            ->findBy(['round' => $round->getId()], ['id' => 'ASC']);

        // Select an answer on the first question
        $firstQuestion = $orderedQuestions[0];
        $answers = $em->getRepository(\App\Entity\Answer::class)->findBy(['question' => $firstQuestion]);
        $answers[0]->setUserSelected(true);
        $em->flush();

        $token = $this->generateToken($user);

        $this->client->request(
            'GET',
            '/api/games/' . $game->getId() . '/rounds/' . $round->getId() . '/questions/' . $orderedQuestions[1]->getId() . '/previous',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotNull($response['question']);

        $hasSelectedAnswer = false;
        foreach ($response['question']['answers'] as $answer) {
            if ($answer['user_selected']) {
                $hasSelectedAnswer = true;
            }
        }
        $this->assertTrue($hasSelectedAnswer);
    }

    public function testPreviousQuestionRequiresAuthentication(): void
    {
        $owner = UserFactory::createOne();
        $data = $this->createGameWithQuestions($owner);

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $questions = $em->getRepository(\App\Entity\Question::class)
            ->findBy(['round' => $data['round']->getId()], ['id' => 'ASC']);

        $this->client->request(
            'GET',
            '/api/games/' . $data['game']->getId() . '/rounds/' . $data['round']->getId() . '/questions/' . $questions[1]->getId() . '/previous'
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
