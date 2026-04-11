<?php

namespace App\Controller\Auth;

use App\Attribute\RateLimited;
use App\Controller\ApiController;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class RefreshController extends ApiController
{
    public function __construct(
        private AuthService $authService,
    ) {
    }

    #[Route('/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    #[RateLimited('api_token_refresh', message: 'Too many refresh attempts, please try again later.')]
    public function refresh(Request $request): JsonResponse
    {
        $data = $this->getJsonBody($request);
        $tokenString = (string) ($data['refresh_token'] ?? '');

        $result = $this->authService->refresh($tokenString);

        return $this->json([
            'token' => $result['token'],
            'refresh_token' => $result['refresh_token'],
            'user' => $this->serializeUser($result['user']),
        ]);
    }
}
