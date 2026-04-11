<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserGameRole;
use App\Service\UserGameService;
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
