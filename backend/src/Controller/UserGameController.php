<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserGame;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserGameController extends AbstractController
{
    #[Route('/api/games/{id}/join', name: 'api_game_join', methods: ['POST'])]
    #[RateLimited('api_general')]
    public function join(Game $game, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        foreach ($game->getUserGames() as $userGame) {
            if ($userGame->getUser()?->getId()?->toString() === $user->getId()?->toString()) {
                return $this->json(['error' => 'You have already joined this game.'], Response::HTTP_CONFLICT);
            }
        }

        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);
        $userGame->setRole('participant');
        $entityManager->persist($userGame);
        $entityManager->flush();

        return $this->json([
            'message' => 'Successfully joined the game.',
            'game_id' => $game->getId()->toString(),
            'role' => 'participant',
        ]);
    }
}
