<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserGameRole;
use App\Service\UserGameService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserGameController extends ApiController
{
    public function __construct(
        private UserGameService $userGameService,
    ) {
    }

    #[Route('/api/games/{id}/join', name: 'api_game_join', methods: ['POST'])]
    #[RateLimited('api_general')]
    #[OA\Post(
        path: '/api/games/{id}/join',
        summary: 'Join a game as a participant',
        security: [['Bearer' => []]],
        tags: ['Games'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully joined the game',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully joined the game.'),
                        new OA\Property(property: 'game_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'role', type: 'string', example: 'participant'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 409, description: 'Already joined this game'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function join(Game $game): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $userGame = $this->userGameService->joinGame($user, $game);

        return $this->json([
            'message' => 'Successfully joined the game.',
            'game_id' => $game->getId()->toString(),
            'role' => UserGameRole::Participant->value,
        ]);
    }
}
