<?php

namespace App\Tests\Integration\Service;

use App\DTO\AnswerDTO;
use App\DTO\QuestionDTO;
use App\Entity\Category;
use App\Entity\Difficulty;
use App\Entity\Round;
use App\Service\OpenTriviaDBProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

#[Group('integration')]
class OpenTriviaDBProviderIntegrationTest extends TestCase
{
    private OpenTriviaDBProvider $provider;

    protected function setUp(): void
    {
        $this->markTestSkipped('This test makes real API calls to Open Trivia DB and is meant for integration testing. It should not be run as part of the regular test suite.');
        $this->provider = new OpenTriviaDBProvider(HttpClient::create());
        // Open Trivia DB rate-limits to 1 request per 5 seconds
        sleep(5);
    }

    private function createRoundWithCategory(string $categoryName): Round
    {
        $category = new Category();
        $category->setName($categoryName);

        $round = new Round();
        $round->setCategory($category);
        $round->setRoundNumber(1);

        return $round;
    }

    private function createDifficulty(string $name): Difficulty
    {
        $difficulty = new Difficulty();
        $difficulty->setName($name);

        return $difficulty;
    }

    public function testFetchesQuestionsFromApi(): void
    {
        $round = $this->createRoundWithCategory('General Knowledge');
        $difficulty = $this->createDifficulty('easy');

        $questions = $this->provider->fetchQuestions($round, $difficulty);

        $this->assertCount(10, $questions);

        foreach ($questions as $question) {
            $this->assertInstanceOf(QuestionDTO::class, $question);
            $this->assertNotEmpty($question->questionText);
            $this->assertCount(4, $question->answers);

            $correctAnswers = array_filter($question->answers, fn(AnswerDTO $a) => $a->isCorrect);
            $this->assertCount(1, $correctAnswers, 'Each question should have exactly one correct answer');

            foreach ($question->answers as $answer) {
                $this->assertInstanceOf(AnswerDTO::class, $answer);
                $this->assertNotEmpty($answer->answerText);
                $this->assertStringNotContainsString('&quot;', $answer->answerText, 'HTML entities should be decoded');
                $this->assertStringNotContainsString('&amp;', $answer->answerText, 'HTML entities should be decoded');
                $this->assertStringNotContainsString('&#039;', $answer->answerText, 'HTML entities should be decoded');
            }
        }
    }

    public function testFetchesQuestionsWithSpecificCategory(): void
    {
        $round = $this->createRoundWithCategory('Science: Computers');
        $difficulty = $this->createDifficulty('medium');

        $questions = $this->provider->fetchQuestions($round, $difficulty);

        $this->assertCount(10, $questions);
        $this->assertInstanceOf(QuestionDTO::class, $questions[0]);
    }

    public function testFetchesQuestionsWithHardDifficulty(): void
    {
        $round = $this->createRoundWithCategory('History');
        $difficulty = $this->createDifficulty('hard');

        $questions = $this->provider->fetchQuestions($round, $difficulty);

        $this->assertCount(10, $questions);
    }
}
