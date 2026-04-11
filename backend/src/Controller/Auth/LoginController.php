<?php

namespace App\Controller\Auth;

use App\Attribute\RateLimited;
use App\Controller\ApiController;
use App\Service\AuthService;
use OpenApi\Attributes as OA;
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
    #[OA\Post(
        path: '/api/login',
        summary: 'Authenticate user and get JWT token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                    new OA\Property(property: 'password', type: 'string', example: 'S3cure!Pass0'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', description: 'JWT access token'),
                        new OA\Property(property: 'refresh_token', type: 'string', description: 'Refresh token'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'username', type: 'string'),
                                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
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
