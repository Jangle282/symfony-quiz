<?php

namespace App\Controller\Auth;

use App\Attribute\RateLimited;
use App\Controller\ApiController;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class RegisterController extends ApiController
{
    public function __construct(
        private AuthService $authService,
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    #[RateLimited('api_registration', message: 'Too many registration attempts, please try again later.')]
    public function register(Request $request): JsonResponse
    {
        $data = $this->getJsonBody($request);

        $username = (string) ($data['username'] ?? '');
        $password = (string) ($data['password'] ?? '');

        $user = $this->authService->register($username, $password);

        return $this->json($this->serializeUser($user), Response::HTTP_CREATED);
    }
}
