<?php

namespace App\Controller\Auth;

use App\Controller\ApiController;
use App\Entity\User;
use App\Service\AuthService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

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
    public function logout(#[CurrentUser] User $user): Response
    {
        $this->authService->logout($user);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
