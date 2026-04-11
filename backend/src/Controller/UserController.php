<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\User;
use App\Exception\NotFoundException;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user')]
class UserController extends ApiController
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    #[Route('/{id}', name: 'api_profile', methods: ['GET'])]
    #[RateLimited('api_general')]
    public function profile(string $id, UserRepository $userRepository): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        if (!$authenticatedUser instanceof User) {
            return $this->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->find($id);
        if (!$user instanceof User) {
            throw new NotFoundException('User not found.');
        }

        $this->denyAccessUnlessGranted('USER_VIEW', $user);

        return $this->json($this->userService->getUserProfile($user));
    }

    #[Route('/{id}/username', name: 'api_profile_update_username', methods: ['PATCH'])]
    #[RateLimited('api_general')]
    public function updateUsername(Request $request, string $id, UserRepository $userRepository): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        if (!$authenticatedUser instanceof User) {
            return $this->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->find($id);
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

    #[Route('/{id}/password', name: 'api_profile_update_password', methods: ['PATCH'])]
    #[RateLimited('api_general')]
    public function updatePassword(Request $request, string $id, UserRepository $userRepository): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        if (!$authenticatedUser instanceof User) {
            return $this->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->find($id);
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
