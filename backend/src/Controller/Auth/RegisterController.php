<?php

namespace App\Controller\Auth;

use App\Attribute\RateLimited;
use App\Controller\ApiController;
use App\Service\AuthService;
use OpenApi\Attributes as OA;
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
    #[OA\Post(
        path: '/api/register',
        summary: 'Register a new user',
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
                response: 201,
                description: 'User created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'username', type: 'string'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 409, description: 'Username already exists'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $data = $this->getJsonBody($request);

        $username = (string) ($data['username'] ?? '');
        $password = (string) ($data['password'] ?? '');

        $user = $this->authService->register($username, $password);

        return $this->json($this->serializeUser($user), Response::HTTP_CREATED);
    }
}
