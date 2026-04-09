<?php

namespace App\Tests\Unit\Service;

use App\DTO\AnswerDTO;
use App\DTO\QuestionDTO;
use App\Entity\Category;
use App\Entity\Difficulty;
use App\Entity\Round;
use App\Service\OpenTriviaDBProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OpenTriviaDBProviderTest extends TestCase
{
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

    private function createMockResponse(array $data): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($data);

        return $response;
    }

    private function buildApiResponse(array $results, int $responseCode = 0): array
    {
        return [
            'response_code' => $responseCode,
            'results' => $results,
        ];
    }

    private function buildResult(
        string $question = 'What is 2+2?',
        string $correctAnswer = '4',
        array $incorrectAnswers = ['3', '5', '6'],
        string $category = 'Science: Mathematics',
        string $difficulty = 'easy',
    ): array {
        return [
            'type' => 'multiple',
            'difficulty' => $difficulty,
            'category' => $category,
            'question' => $question,
            'correct_answer' => $correctAnswer,
            'incorrect_answers' => $incorrectAnswers,
        ];
    }

    public function testFetchQuestionsReturnsCorrectDTOs(): void
    {
        $apiData = $this->buildApiResponse([
            $this->buildResult(),
            $this->buildResult(
                question: 'What is the capital of France?',
                correctAnswer: 'Paris',
                incorrectAnswers: ['London', 'Berlin', 'Madrid'],
                category: 'Geography',
            ),
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://opentdb.com/api.php', $this->callback(function (array $options) {
                return $options['query']['amount'] === 10
                    && $options['query']['category'] === 19
                    && $options['query']['difficulty'] === 'easy'
                    && $options['query']['type'] === 'multiple';
            }))
            ->willReturn($this->createMockResponse($apiData));

        $provider = new OpenTriviaDBProvider($httpClient);
        $round = $this->createRoundWithCategory('Science: Mathematics');
        $difficulty = $this->createDifficulty('easy');

        $questions = $provider->fetchQuestions($round, $difficulty);

        $this->assertCount(2, $questions);
        $this->assertInstanceOf(QuestionDTO::class, $questions[0]);
        $this->assertSame('What is 2+2?', $questions[0]->questionText);
        $this->assertCount(4, $questions[0]->answers);

        $correctAnswers = array_filter($questions[0]->answers, fn(AnswerDTO $a) => $a->isCorrect);
        $this->assertCount(1, $correctAnswers);

        $correctAnswer = array_values($correctAnswers)[0];
        $this->assertSame('4', $correctAnswer->answerText);

        $this->assertSame('What is the capital of France?', $questions[1]->questionText);
    }

    public function testUnknownCategoryOmitsCategoryParam(): void
    {
        $apiData = $this->buildApiResponse([$this->buildResult()]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://opentdb.com/api.php', $this->callback(function (array $options) {
                return !isset($options['query']['category'])
                    && $options['query']['difficulty'] === 'medium';
            }))
            ->willReturn($this->createMockResponse($apiData));

        $provider = new OpenTriviaDBProvider($httpClient);
        $round = $this->createRoundWithCategory('Unknown Category');
        $difficulty = $this->createDifficulty('medium');

        $questions = $provider->fetchQuestions($round, $difficulty);

        $this->assertCount(1, $questions);
    }

    public function testUnknownDifficultyOmitsDifficultyParam(): void
    {
        $apiData = $this->buildApiResponse([$this->buildResult()]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://opentdb.com/api.php', $this->callback(function (array $options) {
                return $options['query']['category'] === 9
                    && !isset($options['query']['difficulty']);
            }))
            ->willReturn($this->createMockResponse($apiData));

        $provider = new OpenTriviaDBProvider($httpClient);
        $round = $this->createRoundWithCategory('General Knowledge');
        $difficulty = $this->createDifficulty('impossible');

        $questions = $provider->fetchQuestions($round, $difficulty);

        $this->assertCount(1, $questions);
    }

    public function testApiErrorCodeOneThrowsException(): void
    {
        $apiData = $this->buildApiResponse([], 1);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($this->createMockResponse($apiData));

        $provider = new OpenTriviaDBProvider($httpClient);
        $round = $this->createRoundWithCategory('General Knowledge');
        $difficulty = $this->createDifficulty('easy');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('response_code 1');

        $provider->fetchQuestions($round, $difficulty);
    }

    public function testApiErrorCodeFiveThrowsException(): void
    {
        $apiData = $this->buildApiResponse([], 5);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($this->createMockResponse($apiData));

        $provider = new OpenTriviaDBProvider($httpClient);
        $round = $this->createRoundWithCategory('General Knowledge');
        $difficulty = $this->createDifficulty('easy');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('response_code 5');

        $provider->fetchQuestions($round, $difficulty);
    }

    public function testHtmlEntitiesAreDecoded(): void
    {
        $apiData = $this->buildApiResponse([
            $this->buildResult(
                question: 'What does &quot;HTML&quot; stand for? It&#039;s &amp; more',
                correctAnswer: 'Hyper &amp; Text',
                incorrectAnswers: ['&quot;Wrong&quot;', '&#039;Also Wrong&#039;', 'Just Wrong'],
            ),
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($this->createMockResponse($apiData));

        $provider = new OpenTriviaDBProvider($httpClient);
        $round = $this->createRoundWithCategory('Science: Computers');
        $difficulty = $this->createDifficulty('easy');

        $questions = $provider->fetchQuestions($round, $difficulty);

        $this->assertSame('What does "HTML" stand for? It\'s & more', $questions[0]->questionText);

        $answerTexts = array_map(fn(AnswerDTO $a) => $a->answerText, $questions[0]->answers);
        $this->assertContains('Hyper & Text', $answerTexts);
        $this->assertContains('"Wrong"', $answerTexts);
        $this->assertContains("'Also Wrong'", $answerTexts);
        $this->assertContains('Just Wrong', $answerTexts);
    }
}
