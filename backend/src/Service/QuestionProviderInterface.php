<?php

namespace App\Service;

use App\DTO\QuestionDTO;
use App\Entity\Difficulty;
use App\Entity\Round;

interface QuestionProviderInterface
{
    /**
     * Fetch questions for a given round and difficulty.
     *
     * @return QuestionDTO[]
     */
    public function fetchQuestions(Round $round, Difficulty $difficulty, int $amount = 10): array;
}
