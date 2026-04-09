<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/user')]
class UserController extends AbstractController
{
    #[Route('/{id}', name: 'api_profile', methods: ['GET'])]
    #[RateLimited('api_general')]
    public function profile(Request $request, string $id, UserRepository $userRepository): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        if (!$authenticatedUser instanceof User) {
            return $this->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->find($id);
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('USER_VIEW', $user);

        $games = [];
        foreach ($user->getUserGames() as $userGame) {
            $game = $userGame->getGame();
            if ($game === null) {
                continue;
            }

            $games[] = [
                'id' => (string) $game->getId(),
                'role' => $userGame->getRole(),
                'joinedAt' => $userGame->getJoinedAt()?->format(DATE_ATOM),
                'createdBy' => (string) $game->getCreatedBy()?->getId(),
                'totalScore' => $game->getTotalScore(),
                'startedAt' => $game->getStartedAt()?->format(DATE_ATOM),
                'completedAt' => $game->getCompletedAt()?->format(DATE_ATOM),
            ];
        }

        return $this->json([
            'user' => [
                'id' => (string) $user->getId(),
                'username' => $user->getUsername(),
                'createdAt' => $user->getCreatedAt()?->format(DATE_ATOM),
                'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
            ],
            'games' => $games,
        ]);
    }

    #[Route('/{id}/username', name: 'api_profile_update_username', methods: ['PATCH'])]
    #[RateLimited('api_general')]
    public function updateUsername(
        Request $request,
        string $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        if (!$authenticatedUser instanceof User) {
            return $this->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->find($id);
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('USER_UPDATE', $user);

        $data = $this->getJsonBody($request);

        $newUsername = trim((string) ($data['username'] ?? ''));
        if ($newUsername === '') {
            return $this->json(['error' => 'Username is required.'], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $userRepository->findOneByUsername($newUsername);
        if ($existingUser !== null && (string) $existingUser->getId() !== (string) $user->getId()) {
            return $this->json(['error' => 'Username already exists.'], Response::HTTP_CONFLICT);
        }

        $user->setUsername($newUsername);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->json([
            'user' => [
                'id' => (string) $user->getId(),
                'username' => $user->getUsername(),
                'createdAt' => $user->getCreatedAt()?->format(DATE_ATOM),
                'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
            ],
        ]);
    }

    #[Route('/{id}/password', name: 'api_profile_update_password', methods: ['PATCH'])]
    #[RateLimited('api_general')]
    public function updatePassword(
        Request $request,
        string $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        if (!$authenticatedUser instanceof User) {
            return $this->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->find($id);
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('USER_UPDATE', $user);

        $data = $this->getJsonBody($request);

        $currentPassword = (string) ($data['current_password'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(['error' => 'Current password is invalid.'], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($newPassword) < 10 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            return $this->json(['error' => 'New password must be at least 10 characters and include letters, numbers, and symbols.'], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $user->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->json(['message' => 'Password updated successfully.']);
    }

    private function getJsonBody(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            return [];
        }
    }
}
