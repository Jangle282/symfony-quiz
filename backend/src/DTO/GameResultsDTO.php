<?php

namespace App\DTO;

final readonly class GameResultsDTO
{
    /**
     * @param GameResultQuestionDTO[] $questions
     */
    public function __construct(
        public string $gameId,
        public int $totalScore,
        public int $totalQuestions,
        public array $questions,
    ) {
    }
}
