<?php

namespace App\Service;

use App\DTO\AnswerDTO;
use App\DTO\QuestionDTO;
use App\Entity\Difficulty;
use App\Entity\Round;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenTriviaDBProvider implements QuestionProviderInterface
{
    private const string API_URL = 'https://opentdb.com/api.php';

    private const array CATEGORY_MAP = [
        'General Knowledge' => 9,
        'Entertainment: Books' => 10,
        'Entertainment: Film' => 11,
        'Entertainment: Music' => 12,
        'Entertainment: Musicals & Theatres' => 13,
        'Entertainment: Television' => 14,
        'Entertainment: Video Games' => 15,
        'Entertainment: Board Games' => 16,
        'Science & Nature' => 17,
        'Science: Computers' => 18,
        'Science: Mathematics' => 19,
        'Mythology' => 20,
        'Sports' => 21,
        'Geography' => 22,
        'History' => 23,
        'Politics' => 24,
        'Art' => 25,
        'Celebrities' => 26,
        'Animals' => 27,
        'Vehicles' => 28,
        'Entertainment: Comics' => 29,
        'Science: Gadgets' => 30,
        'Entertainment: Japanese Anime & Manga' => 31,
        'Entertainment: Cartoon & Animations' => 32,
    ];

    private const array DIFFICULTY_MAP = [
        'easy' => 'easy',
        'medium' => 'medium',
        'hard' => 'hard',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return QuestionDTO[]
     */
    public function fetchQuestions(Round $round, Difficulty $difficulty, int $amount = 10): array
    {
        $query = [
            'amount' => $amount,
            'type' => 'multiple',
        ];

        $categoryName = $round->getCategory()->getName();
        if (isset(self::CATEGORY_MAP[$categoryName])) {
            $query['category'] = self::CATEGORY_MAP[$categoryName];
        }

        $difficultyName = $difficulty->getName();
        if (isset(self::DIFFICULTY_MAP[$difficultyName])) {
            $query['difficulty'] = self::DIFFICULTY_MAP[$difficultyName];
        }

        $response = $this->httpClient->request('GET', self::API_URL, [
            'query' => $query,
        ]);

        $data = $response->toArray();

        if ($data['response_code'] !== 0) {
            throw new \RuntimeException(sprintf(
                'Open Trivia DB API error: response_code %d',
                $data['response_code'],
            ));
        }

        $questions = [];

        foreach ($data['results'] as $result) {
            $questionText = html_entity_decode($result['question'], ENT_QUOTES | ENT_HTML5);

            $answers = [];
            $answers[] = new AnswerDTO(
                answerText: html_entity_decode($result['correct_answer'], ENT_QUOTES | ENT_HTML5),
                isCorrect: true,
            );

            foreach ($result['incorrect_answers'] as $incorrectAnswer) {
                $answers[] = new AnswerDTO(
                    answerText: html_entity_decode($incorrectAnswer, ENT_QUOTES | ENT_HTML5),
                    isCorrect: false,
                );
            }

            shuffle($answers);

            $questions[] = new QuestionDTO(
                questionText: $questionText,
                answers: $answers,
            );
        }

        return $questions;
    }
}
