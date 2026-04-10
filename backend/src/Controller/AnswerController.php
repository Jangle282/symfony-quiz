<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Answer;
use App\Entity\Game;
use App\Entity\Question;
use App\Entity\Round;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnswerController extends AbstractController
{
    #[Route('/api/games/{gameId}/rounds/{roundId}/questions/{questionId}/answers/{answerId}/select', name: 'api_game_select_answer', methods: ['POST'])]
    #[RateLimited('api_general')]
    public function selectAnswer(
        string $gameId,
        string $roundId,
        string $questionId,
        string $answerId,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $game = $entityManager->find(Game::class, $gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('GAME_VIEW', $game);

        if ($game->getCompletedAt() !== null) {
            return $this->json(['error' => 'Game is already completed.'], Response::HTTP_BAD_REQUEST);
        }

        $round = $entityManager->find(Round::class, $roundId);
        if (!$round || $round->getGame()?->getId()?->toString() !== $game->getId()->toString()) {
            return $this->json(['error' => 'Round not found.'], Response::HTTP_NOT_FOUND);
        }

        $question = $entityManager->find(Question::class, $questionId);
        if (!$question || $question->getRound()?->getId()?->toString() !== $round->getId()->toString()) {
            return $this->json(['error' => 'Question not found.'], Response::HTTP_NOT_FOUND);
        }

        $selectedAnswer = $entityManager->find(Answer::class, $answerId);
        if (!$selectedAnswer || $selectedAnswer->getQuestion()?->getId()?->toString() !== $question->getId()->toString()) {
            return $this->json(['error' => 'Answer not found.'], Response::HTTP_NOT_FOUND);
        }

        foreach ($question->getAnswers() as $answer) {
            $answer->setUserSelected($answer->getId()->toString() === $selectedAnswer->getId()->toString());
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Answer selected.',
            'question_id' => $question->getId()->toString(),
            'selected_answer_id' => $selectedAnswer->getId()->toString(),
        ]);
    }
}
