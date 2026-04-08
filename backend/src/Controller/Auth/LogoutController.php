<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class LogoutController extends AbstractController
{
    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(RefreshTokenRepository $refreshTokenRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if ($user instanceof User) {
            $refreshTokenRepository->revokeAllForUser($user);
        }

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
