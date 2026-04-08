<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/api/games/{id}', name: 'api_game_delete', methods: ['DELETE'])]
    #[RateLimited('api_general')]
    public function delete(Game $game, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('GAME_DELETE', $game);

        $entityManager->remove($game);
        $entityManager->flush();

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
