<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Exception\NotFoundException;
use App\Service\AnswerService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
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
    #[OA\Post(
        path: '/api/games/{gameId}/rounds/{roundId}/questions/{questionId}/answers/{answerId}/select',
        summary: 'Select an answer for a question',
        security: [['Bearer' => []]],
        tags: ['Answers'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'roundId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'questionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'answerId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Answer selected',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Answer selected.'),
                        new OA\Property(property: 'question_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'selected_answer_id', type: 'string', format: 'uuid'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Game is already completed'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Game, round, question, or answer not found'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
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
