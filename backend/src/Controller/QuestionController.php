<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Exception\NotFoundException;
use App\Service\QuestionNavigationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends ApiController
{
    public function __construct(
        private QuestionNavigationService $questionNavigationService,
    ) {
    }

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
            throw new NotFoundException('Game not found.');
        }

        $this->denyAccessUnlessGranted('GAME_VIEW', $game);

        $question = $this->questionNavigationService->getNextQuestion($game, $roundId, $questionId);

        return $this->json(['question' => $question]);
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
            throw new NotFoundException('Game not found.');
        }

        $this->denyAccessUnlessGranted('GAME_VIEW', $game);

        $question = $this->questionNavigationService->getPreviousQuestion($game, $roundId, $questionId);

        return $this->json(['question' => $question]);
    }
}
