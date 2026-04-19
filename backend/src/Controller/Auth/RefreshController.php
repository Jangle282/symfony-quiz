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
class RefreshController extends ApiController
{
    public function __construct(
        private AuthService $authService,
    ) {
    }

    #[Route('/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    #[RateLimited('api_token_refresh', message: 'Too many refresh attempts, please try again later.')]
    #[OA\Post(
        path: '/api/token/refresh',
        summary: 'Refresh JWT token using a refresh token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', description: 'New JWT access token'),
                        new OA\Property(property: 'refresh_token', type: 'string', description: 'New rotated refresh token'),
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
            new OA\Response(response: 400, description: 'Refresh token is required'),
            new OA\Response(response: 401, description: 'Invalid or expired refresh token'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
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
