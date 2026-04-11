<?php

namespace App\Controller\Auth;

use App\Attribute\RateLimited;
use App\Controller\ApiController;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class LoginController extends ApiController
{
    public function __construct(
        private AuthService $authService,
    ) {
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    #[RateLimited('api_login', message: 'Too many login attempts, please try again later.')]
    public function login(Request $request): JsonResponse
    {
        $data = $this->getJsonBody($request);

        $username = (string) ($data['username'] ?? '');
        $password = (string) ($data['password'] ?? '');

        $result = $this->authService->login($username, $password);

        return $this->json([
            'token' => $result['token'],
            'refresh_token' => $result['refresh_token'],
            'user' => $this->serializeUser($result['user']),
        ]);
    }
}
