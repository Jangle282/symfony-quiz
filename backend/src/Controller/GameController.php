<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Service\GameService;
use App\Service\QuestionService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends ApiController
{
    #[Route('/api/games', name: 'api_game_create', methods: ['POST'])]
    #[RateLimited('api_general')]
    #[OA\Post(
        path: '/api/games',
        summary: 'Create a new game',
        security: [['Bearer' => []]],
        tags: ['Games'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'difficulty', type: 'string', enum: ['easy', 'medium', 'hard'], example: 'medium'),
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Friday Quiz'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Game created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string', nullable: true),
                        new OA\Property(property: 'difficulty', type: 'string'),
                        new OA\Property(
                            property: 'round',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'round_number', type: 'integer'),
                                new OA\Property(property: 'category', type: 'string'),
                            ]
                        ),
                        new OA\Property(
                            property: 'first_question',
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
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid difficulty'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 429, description: 'Too many requests'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function create(
        Request $request,
        GameService $gameService,
        QuestionService $questionService,
    ): JsonResponse {
        $user = $this->getUser();
        $data = $request->toArray();
        $difficultyName = $data['difficulty'] ?? 'medium';
        $gameName = $data['name'] ?? null;

        $result = $gameService->createGame($user, $difficultyName, $gameName);

        $game = $result['game'];
        $round = $result['round'];
        $category = $result['category'];
        $difficulty = $result['difficulty'];

        $questionService->createQuestionsAndAnswers([$round], $difficulty, 5);

        $firstQuestion = $round->getQuestions()->first();
        $questionData = null;
        if ($firstQuestion) {
            $answers = [];
            foreach ($firstQuestion->getAnswers() as $answer) {
                $answers[] = [
                    'id' => $answer->getId()->toString(),
                    'answer_text' => $answer->getAnswerText(),
                ];
            }
            $questionData = [
                'id' => $firstQuestion->getId()->toString(),
                'question_text' => $firstQuestion->getQuestionText(),
                'answers' => $answers,
            ];
        }

        return $this->json([
            'id' => $game->getId()->toString(),
            'name' => $game->getName(),
            'difficulty' => $difficulty->getName(),
            'round' => [
                'id' => $round->getId()->toString(),
                'round_number' => $round->getRoundNumber(),
                'category' => $category->getName(),
            ],
            'first_question' => $questionData,
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/games/{id}', name: 'api_game_delete', methods: ['DELETE'])]
    #[RateLimited('api_general')]
    #[OA\Delete(
        path: '/api/games/{id}',
        summary: 'Delete a game',
        security: [['Bearer' => []]],
        tags: ['Games'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Game deleted'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Game not found'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function delete(Game $game, GameService $gameService): Response
    {
        $this->denyAccessUnlessGranted('GAME_DELETE', $game);
        $gameService->deleteGame($game);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/games/{id}', name: 'api_game_show', methods: ['GET'])]
    #[RateLimited('api_general')]
    #[OA\Get(
        path: '/api/games/{id}',
        summary: 'Get game details',
        security: [['Bearer' => []]],
        tags: ['Games'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Game details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string', nullable: true),
                        new OA\Property(property: 'difficulty', type: 'string'),
                        new OA\Property(property: 'total_score', type: 'integer'),
                        new OA\Property(property: 'started_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(
                            property: 'rounds',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'round_number', type: 'integer'),
                                    new OA\Property(property: 'category', type: 'string'),
                                    new OA\Property(property: 'total_questions', type: 'integer'),
                                    new OA\Property(property: 'answered_questions', type: 'integer'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'current_question',
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
            new OA\Response(response: 404, description: 'Game not found'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function show(Game $game, GameService $gameService): JsonResponse
    {
        $this->denyAccessUnlessGranted('GAME_VIEW', $game);
        return $this->json($gameService->getGameData($game));
    }

    #[Route('/api/games/{id}/complete', name: 'api_game_complete', methods: ['POST'])]
    #[RateLimited('api_general')]
    #[OA\Post(
        path: '/api/games/{id}/complete',
        summary: 'Complete a game',
        security: [['Bearer' => []]],
        tags: ['Games'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Game completed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Game completed.'),
                        new OA\Property(property: 'game_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'total_score', type: 'integer'),
                        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Game is already completed'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function complete(Game $game, GameService $gameService): JsonResponse
    {
        $this->denyAccessUnlessGranted('GAME_VIEW', $game);
        $result = $gameService->completeGame($game);
        return $this->json([
            'message' => 'Game completed.',
            'game_id' => $result['game_id'],
            'total_score' => $result['total_score'],
            'completed_at' => $result['completed_at'],
        ]);
    }

    #[Route('/api/games/{id}/results', name: 'api_game_results', methods: ['GET'])]
    #[RateLimited('api_general')]
    #[OA\Get(
        path: '/api/games/{id}/results',
        summary: 'Get game results',
        security: [['Bearer' => []]],
        tags: ['Games'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Game results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'game_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'total_score', type: 'integer'),
                        new OA\Property(property: 'total_questions', type: 'integer'),
                        new OA\Property(
                            property: 'questions',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'question_id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'question_text', type: 'string'),
                                    new OA\Property(property: 'correct_answer', type: 'string', nullable: true),
                                    new OA\Property(property: 'selected_answer', type: 'string', nullable: true),
                                    new OA\Property(property: 'is_correct', type: 'boolean'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Game not found'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function results(Game $game, GameService $gameService): JsonResponse
    {
        $this->denyAccessUnlessGranted('GAME_VIEW', $game);
        $results = $gameService->getResults($game);
        return $this->json([
            'game_id' => $results->gameId,
            'total_score' => $results->totalScore,
            'total_questions' => $results->totalQuestions,
            'questions' => array_map(fn($q) => [
                'question_id' => $q->questionId,
                'question_text' => $q->questionText,
                'correct_answer' => $q->correctAnswer,
                'selected_answer' => $q->selectedAnswer,
                'is_correct' => $q->isCorrect,
            ], $results->questions),
        ]);
    }
}
