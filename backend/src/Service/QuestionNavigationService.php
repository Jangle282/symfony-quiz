<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Question;
use App\Entity\Round;
use App\Exception\NotFoundException;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuestionNavigationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuestionRepository $questionRepository,
    ) {
    }

    public function getNextQuestion(Game $game, string $roundId, string $questionId): ?array
    {
        [$round, $currentQuestion] = $this->resolveEntities($game, $roundId, $questionId);

        $nextQuestion = $this->questionRepository->findNextInRound($round, $currentQuestion->getId());

        return $nextQuestion ? $this->formatQuestionData($nextQuestion) : null;
    }

    public function getPreviousQuestion(Game $game, string $roundId, string $questionId): ?array
    {
        [$round, $currentQuestion] = $this->resolveEntities($game, $roundId, $questionId);

        $previousQuestion = $this->questionRepository->findPreviousInRound($round, $currentQuestion->getId());

        return $previousQuestion ? $this->formatQuestionData($previousQuestion) : null;
    }

    private function resolveEntities(Game $game, string $roundId, string $questionId): array
    {
        $round = $this->entityManager->find(Round::class, $roundId);
        if (!$round || $round->getGame()?->getId()?->toString() !== $game->getId()->toString()) {
            throw new NotFoundException('Round not found.');
        }

        $currentQuestion = $this->entityManager->find(Question::class, $questionId);
        if (!$currentQuestion || $currentQuestion->getRound()?->getId()?->toString() !== $round->getId()->toString()) {
            throw new NotFoundException('Question not found.');
        }

        return [$round, $currentQuestion];
    }

    private function formatQuestionData(Question $question): array
    {
        $answers = [];
        foreach ($question->getAnswers() as $answer) {
            $answers[] = [
                'id' => $answer->getId()->toString(),
                'answer_text' => $answer->getAnswerText(),
                'user_selected' => $answer->isUserSelected(),
            ];
        }

        return [
            'id' => $question->getId()->toString(),
            'question_text' => $question->getQuestionText(),
            'answers' => $answers,
        ];
    }
}
