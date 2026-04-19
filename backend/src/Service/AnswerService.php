<?php

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Game;
use App\Entity\Question;
use App\Entity\Round;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;

class AnswerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function selectAnswer(Game $game, string $roundId, string $questionId, string $answerId): array
    {
        if ($game->getCompletedAt() !== null) {
            throw new ValidationException('Game is already completed.');
        }

        $round = $this->entityManager->find(Round::class, $roundId);
        if (!$round || $round->getGame()?->getId()?->toString() !== $game->getId()->toString()) {
            throw new NotFoundException('Round not found.');
        }

        $question = $this->entityManager->find(Question::class, $questionId);
        if (!$question || $question->getRound()?->getId()?->toString() !== $round->getId()->toString()) {
            throw new NotFoundException('Question not found.');
        }

        $selectedAnswer = $this->entityManager->find(Answer::class, $answerId);
        if (!$selectedAnswer || $selectedAnswer->getQuestion()?->getId()?->toString() !== $question->getId()->toString()) {
            throw new NotFoundException('Answer not found.');
        }

        foreach ($question->getAnswers() as $answer) {
            $answer->setUserSelected($answer->getId()->toString() === $selectedAnswer->getId()->toString());
        }

        $this->entityManager->flush();

        return [
            'message' => 'Answer selected.',
            'question_id' => $question->getId()->toString(),
            'selected_answer_id' => $selectedAnswer->getId()->toString(),
        ];
    }
}
