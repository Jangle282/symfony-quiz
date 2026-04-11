<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Exception\NotFoundException;
use App\Service\AnswerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AnswerController extends ApiController
{
    public function __construct(
        private AnswerService $answerService,
    ) {
    }

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
            throw new NotFoundException('Game not found.');
        }

        $this->denyAccessUnlessGranted('GAME_VIEW', $game);

        $result = $this->answerService->selectAnswer($game, $roundId, $questionId, $answerId);

        return $this->json($result);
    }
}
