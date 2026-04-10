<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Entity\Question;
use App\Entity\Round;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    #[Route('/api/games/{gameId}/rounds/{roundId}/questions/{questionId}/next', name: 'api_game_next_question', methods: ['GET'])]
    #[RateLimited('api_general')]
    public function nextQuestion(
        string $gameId,
        string $roundId,
        string $questionId,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $game = $entityManager->find(Game::class, $gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('GAME_VIEW', $game);

        $round = $entityManager->find(Round::class, $roundId);
        if (!$round || $round->getGame()?->getId()?->toString() !== $game->getId()->toString()) {
            return $this->json(['error' => 'Round not found.'], Response::HTTP_NOT_FOUND);
        }

        $currentQuestion = $entityManager->find(Question::class, $questionId);
        if (!$currentQuestion || $currentQuestion->getRound()?->getId()?->toString() !== $round->getId()->toString()) {
            return $this->json(['error' => 'Question not found.'], Response::HTTP_NOT_FOUND);
        }

        $questions = $entityManager->getRepository(Question::class)
            ->findBy(['round' => $round], ['id' => 'ASC']);

        $nextQuestion = null;
        $found = false;
        foreach ($questions as $question) {
            if ($found) {
                $nextQuestion = $question;
                break;
            }
            if ($question->getId()->toString() === $currentQuestion->getId()->toString()) {
                $found = true;
            }
        }

        if (!$nextQuestion) {
            return $this->json(['question' => null]);
        }

        return $this->json(['question' => $this->serializeQuestion($nextQuestion)]);
    }

    #[Route('/api/games/{gameId}/rounds/{roundId}/questions/{questionId}/previous', name: 'api_game_previous_question', methods: ['GET'])]
    #[RateLimited('api_general')]
    public function previousQuestion(
        string $gameId,
        string $roundId,
        string $questionId,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $game = $entityManager->find(Game::class, $gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('GAME_VIEW', $game);

        $round = $entityManager->find(Round::class, $roundId);
        if (!$round || $round->getGame()?->getId()?->toString() !== $game->getId()->toString()) {
            return $this->json(['error' => 'Round not found.'], Response::HTTP_NOT_FOUND);
        }

        $currentQuestion = $entityManager->find(Question::class, $questionId);
        if (!$currentQuestion || $currentQuestion->getRound()?->getId()?->toString() !== $round->getId()->toString()) {
            return $this->json(['error' => 'Question not found.'], Response::HTTP_NOT_FOUND);
        }

        $questions = $entityManager->getRepository(Question::class)
            ->findBy(['round' => $round], ['id' => 'ASC']);

        $previousQuestion = null;
        foreach ($questions as $question) {
            if ($question->getId()->toString() === $currentQuestion->getId()->toString()) {
                break;
            }
            $previousQuestion = $question;
        }

        if (!$previousQuestion) {
            return $this->json(['question' => null]);
        }

        return $this->json(['question' => $this->serializeQuestion($previousQuestion)]);
    }

    private function serializeQuestion(Question $question): array
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
