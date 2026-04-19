<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Exception\NotFoundException;
use App\Service\QuestionNavigationService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends ApiController
{
    public function __construct(
        private QuestionNavigationService $questionNavigationService,
    ) {
    }

    #[Route('/api/games/{game_id}/rounds/{round_id}/questions/{question_id}/next', name: 'api_game_next_question', methods: ['GET'])]
    #[RateLimited('api_general')]
    #[OA\Get(
        path: '/api/games/{game_id}/rounds/{round_id}/questions/{question_id}/next',
        summary: 'Get next question in a round',
        security: [['Bearer' => []]],
        tags: ['Questions'],
        parameters: [
            new OA\Parameter(name: 'game_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'round_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'question_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Next question or null if no more questions',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'question',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'question_text', type: 'string'),
                                new OA\Property(
                                    property: 'answers',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'answer_text', type: 'string'),
                                            new OA\Property(property: 'user_selected', type: 'boolean'),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Game, round, or question not found'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function nextQuestion(
        string $game_id,
        string $round_id,
        string $question_id,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $game = $entityManager->find(Game::class, $game_id);
        if (!$game) {
            throw new NotFoundException('Game not found.');
        }

        $this->denyAccessUnlessGranted('GAME_VIEW', $game);

        $question = $this->questionNavigationService->getNextQuestion($game, $round_id, $question_id);

        return $this->json(['question' => $question]);
    }

    #[Route('/api/games/{game_id}/rounds/{round_id}/questions/{question_id}/previous', name: 'api_game_previous_question', methods: ['GET'])]
    #[RateLimited('api_general')]
    #[OA\Get(
        path: '/api/games/{game_id}/rounds/{round_id}/questions/{question_id}/previous',
        summary: 'Get previous question in a round',
        security: [['Bearer' => []]],
        tags: ['Questions'],
        parameters: [
            new OA\Parameter(name: 'game_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'round_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'question_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Previous question or null if at the first question',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'question',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'question_text', type: 'string'),
                                new OA\Property(
                                    property: 'answers',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'answer_text', type: 'string'),
                                            new OA\Property(property: 'user_selected', type: 'boolean'),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Game, round, or question not found'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function previousQuestion(
        string $game_id,
        string $round_id,
        string $question_id,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $game = $entityManager->find(Game::class, $game_id);
        if (!$game) {
            throw new NotFoundException('Game not found.');
        }

        $this->denyAccessUnlessGranted('GAME_VIEW', $game);

        $question = $this->questionNavigationService->getPreviousQuestion($game, $round_id, $question_id);

        return $this->json(['question' => $question]);
    }
}
