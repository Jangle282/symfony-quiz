<?php

namespace App\Tests\Unit\Service;

use App\Entity\UserGameRole;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Service\AnswerService;
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

class AnswerServiceTest extends KernelTestCase
{
    use Factories;

    private AnswerService $answerService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->answerService = static::getContainer()->get(AnswerService::class);
    }

    public function testSelectAnswerSucceeds(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $category = CategoryFactory::createOne(['name' => 'General Knowledge']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);
        $question = QuestionFactory::createOne(['round' => $round, 'questionText' => 'What is 2+2?']);
        $correct = AnswerFactory::createOne(['question' => $question, 'answerText' => '4', 'isCorrect' => true]);
        $wrong = AnswerFactory::createOne(['question' => $question, 'answerText' => '5', 'isCorrect' => false]);

        $result = $this->answerService->selectAnswer(
            $game->_real(),
            $round->getId()->toString(),
            $question->getId()->toString(),
            $correct->getId()->toString(),
        );

        $this->assertSame('Answer selected.', $result['message']);
        $this->assertSame($correct->getId()->toString(), $result['selected_answer_id']);
    }

    public function testSelectAnswerThrowsOnCompletedGame(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $category = CategoryFactory::createOne(['name' => 'General Knowledge']);
        $game = GameFactory::createOne([
            'createdBy' => $user,
            'difficulty' => $difficulty,
            'completedAt' => new \DateTimeImmutable(),
        ]);

        $round = RoundFactory::createOne(['game' => $game, 'category' => $category, 'roundNumber' => 1]);
        $question = QuestionFactory::createOne(['round' => $round, 'questionText' => 'Q?']);
        $answer = AnswerFactory::createOne(['question' => $question, 'answerText' => 'A', 'isCorrect' => true]);

        $this->expectException(ValidationException::class);
        $this->answerService->selectAnswer(
            $game->_real(),
            $round->getId()->toString(),
            $question->getId()->toString(),
            $answer->getId()->toString(),
        );
    }

    public function testSelectAnswerThrowsOnInvalidRound(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty]);

        $this->expectException(NotFoundException::class);
        $this->answerService->selectAnswer(
            $game->_real(),
            '00000000-0000-0000-0000-000000000000',
            '00000000-0000-0000-0000-000000000001',
            '00000000-0000-0000-0000-000000000002',
        );
    }
}
