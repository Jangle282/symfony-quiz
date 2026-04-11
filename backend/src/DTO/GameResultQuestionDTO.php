<?php

namespace App\DTO;

final readonly class GameResultQuestionDTO
{
    public function __construct(
        public string $questionId,
        public string $questionText,
        public ?string $correctAnswer,
        public ?string $selectedAnswer,
        public bool $isCorrect,
    ) {
    }
}
