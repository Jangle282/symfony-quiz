<?php

namespace App\DTO;

final readonly class QuestionDTO
{
    /**
     * @param AnswerDTO[] $answers
     */
    public function __construct(
        public string $questionText,
        public array $answers,
    ) {
    }
}
