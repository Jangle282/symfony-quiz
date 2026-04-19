<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\User;
use App\Exception\NotFoundException;
use App\Repository\UserRepository;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user')]
class UserController extends ApiController
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    #[Route('/{user_id}', name: 'api_profile', methods: ['GET'])]
    #[RateLimited('api_general')]
    #[OA\Get(
        path: '/api/user/{user_id}',
        summary: 'Get user profile and games',
        security: [['Bearer' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile with games',
                content: new OA\JsonContent(
                    properties: [
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
                        new OA\Property(
                            property: 'games',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'role', type: 'string'),
                                    new OA\Property(property: 'joinedAt', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'createdBy', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'totalScore', type: 'integer'),
                                    new OA\Property(property: 'startedAt', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'completedAt', type: 'string', format: 'date-time', nullable: true),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function profile(string $user_id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($user_id);
        if (!$user instanceof User) {
            throw new NotFoundException('User not found.');
        }

        $this->denyAccessUnlessGranted('USER_VIEW', $user);

        return $this->json($this->userService->getUserProfile($user));
    }

    #[Route('/{user_id}/username', name: 'api_profile_update_username', methods: ['PATCH'])]
    #[RateLimited('api_general')]
    #[OA\Patch(
        path: '/api/user/{user_id}/username',
        summary: 'Update username',
        security: [['Bearer' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'newusername'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Username updated',
                content: new OA\JsonContent(
                    properties: [
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
            new OA\Response(response: 400, description: 'Username is required'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 409, description: 'Username already exists'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function updateUsername(Request $request, string $user_id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($user_id);
        if (!$user instanceof User) {
            throw new NotFoundException('User not found.');
        }

        $this->denyAccessUnlessGranted('USER_UPDATE', $user);

        $data = $this->getJsonBody($request);
        $newUsername = (string) ($data['username'] ?? '');

        $user = $this->userService->updateUsername($user, $newUsername);

        return $this->json([
            'user' => $this->serializeUser($user),
        ]);
    }

    #[Route('/{user_id}/password', name: 'api_profile_update_password', methods: ['PATCH'])]
    #[RateLimited('api_general')]
    #[OA\Patch(
        path: '/api/user/{user_id}/password',
        summary: 'Update password',
        security: [['Bearer' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'new_password'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string'),
                    new OA\Property(property: 'new_password', type: 'string', example: 'N3w!Passw0rd'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Password updated successfully.'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid current password or new password validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function updatePassword(Request $request, string $user_id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($user_id);
        if (!$user instanceof User) {
            throw new NotFoundException('User not found.');
        }

        $this->denyAccessUnlessGranted('USER_UPDATE', $user);

        $data = $this->getJsonBody($request);
        $currentPassword = (string) ($data['current_password'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');

        $this->userService->updatePassword($user, $currentPassword, $newPassword);

        return $this->json(['message' => 'Password updated successfully.']);
    }
}
