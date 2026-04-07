<?php

namespace App\Controller;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'limiter.api_general')] private RateLimiterFactory $generalLimiter,
    ) {
    }

    #[Route('/api/games/{id}', name: 'api_game_delete', methods: ['DELETE'])]
    public function delete(Game $game, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $limiter = $this->generalLimiter->create($request->getClientIp() ?? 'anonymous');
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Too many requests, please try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $this->denyAccessUnlessGranted('GAME_DELETE', $game);

        $entityManager->remove($game);
        $entityManager->flush();

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
