<?php

namespace App\DTO;

final readonly class AnswerDTO
{
    public function __construct(
        public string $answerText,
        public bool $isCorrect,
    ) {
    }
}
