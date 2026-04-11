<?php

namespace App\Tests\Unit\Service;

use App\Entity\UserGameRole;
use App\Exception\NotFoundException;
use App\Service\QuestionNavigationService;
use App\Tests\Factory\AnswerFactory;
use App\Tests\Factory\CategoryFactory;
use App\Tests\Factory\DifficultyFactory;
use App\Tests\Factory\GameFactory;
use App\Tests\Factory\QuestionFactory;
use App\Tests\Factory\RoundFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Factory\UserGameFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class QuestionNavigationServiceTest extends KernelTestCase
{
    use Factories;

    private QuestionNavigationService $navService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->navService = static::getContainer()->get(QuestionNavigationService::class);
    }

    public function testGetNextQuestionReturnsNext(): void
    {
        [$game, $round, $first, $second] = $this->createGameWithTwoQuestions();

        $next = $this->navService->getNextQuestion(
            $game->_real(),
            $round->getId()->toString(),
            $first->getId()->toString(),
        );

        $this->assertNotNull($next);
        $this->assertSame($second->getId()->toString(), $next['id']);
    }

    public function testGetNextQuestionReturnsNullAtEnd(): void
    {
        [$game, $round, $first, $second] = $this->createGameWithTwoQuestions();

        $next = $this->navService->getNextQuestion(
            $game->_real(),
            $round->getId()->toString(),
            $second->getId()->toString(),
        );

        $this->assertNull($next);
    }

    public function testGetPreviousQuestionReturnsPrevious(): void
    {
        [$game, $round, $first, $second] = $this->createGameWithTwoQuestions();

        $prev = $this->navService->getPreviousQuestion(
            $game->_real(),
            $round->getId()->toString(),
            $second->getId()->toString(),
        );

        $this->assertNotNull($prev);
        $this->assertSame($first->getId()->toString(), $prev['id']);
    }

    public function testGetPreviousQuestionReturnsNullAtStart(): void
    {
        [$game, $round, $first, $second] = $this->createGameWithTwoQuestions();

        $prev = $this->navService->getPreviousQuestion(
            $game->_real(),
            $round->getId()->toString(),
            $first->getId()->toString(),
        );

        $this->assertNull($prev);
    }

    public function testThrowsOnInvalidRound(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty]);

        $this->expectException(NotFoundException::class);
        $this->navService->getNextQuestion(
            $game->_real(),
            '00000000-0000-0000-0000-000000000000',
            '00000000-0000-0000-0000-000000000001',
        );
    }

    private function createGameWithTwoQuestions(): array
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $category = CategoryFactory::createOne(['name' => 'General Knowledge']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);
        $q1 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Q1?']);
        AnswerFactory::createOne(['question' => $q1, 'answerText' => 'A1', 'isCorrect' => true]);
        $q2 = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Q2?']);
        AnswerFactory::createOne(['question' => $q2, 'answerText' => 'A2', 'isCorrect' => true]);

        // Sort by UUID to match DB ordering (ORDER BY id ASC)
        $questions = [$q1, $q2];
        usort($questions, fn($a, $b) => strcmp($a->getId()->toString(), $b->getId()->toString()));

        return [$game, $round, $questions[0], $questions[1]];
    }
}
