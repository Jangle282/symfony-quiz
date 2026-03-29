<?php

namespace App\Controller;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class GameController extends AbstractController
{
    #[Route('/api/games/{id}', name: 'api_game_delete', methods: ['DELETE'])]
    public function delete(Game $game, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('GAME_DELETE', $game);

        $tokenValue = $request->headers->get('X-CSRF-Token') ?? $request->toArray()['csrf_token'] ?? '';
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('game_delete', $tokenValue))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($game);
        $entityManager->flush();

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
