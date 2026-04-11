<?php

namespace App\Controller\Auth;

use App\Controller\ApiController;
use App\Entity\User;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class LogoutController extends ApiController
{
    public function __construct(
        private AuthService $authService,
    ) {
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if ($user instanceof User) {
            $this->authService->logout($user);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
