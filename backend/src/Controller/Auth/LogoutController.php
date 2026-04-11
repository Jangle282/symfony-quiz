<?php

namespace App\Controller\Auth;

use App\Controller\ApiController;
use App\Entity\User;
use App\Service\AuthService;
use OpenApi\Attributes as OA;
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
    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout and revoke all refresh tokens',
        security: [['Bearer' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 204, description: 'Successfully logged out'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
