<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\ConflictException;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private PasswordValidator $passwordValidator,
    ) {
    }

    public function getUserProfile(User $user): array
    {
        $games = [];
        foreach ($user->getUserGames() as $userGame) {
            $game = $userGame->getGame();
            if ($game === null) {
                continue;
            }

            $totalScore = 0;
            foreach ($game->getRounds() as $round) {
                foreach ($round->getQuestions() as $question) {
                    foreach ($question->getAnswers() as $answer) {
                        if ($answer->isUserSelected() && $answer->isCorrect()) {
                            $totalScore++;
                        }
                    }
                }
            }

            $games[] = [
                'id' => (string) $game->getId(),
                'role' => $userGame->getRole()?->value,
                'joinedAt' => $userGame->getJoinedAt()?->format(DATE_ATOM),
                'createdBy' => (string) $game->getCreatedBy()?->getId(),
                'totalScore' => $totalScore,
                'startedAt' => $game->getStartedAt()?->format(DATE_ATOM),
                'completedAt' => $game->getCompletedAt()?->format(DATE_ATOM),
            ];
        }

        return [
            'user' => [
                'id' => (string) $user->getId(),
                'username' => $user->getUsername(),
                'createdAt' => $user->getCreatedAt()?->format(DATE_ATOM),
                'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
            ],
            'games' => $games,
        ];
    }

    public function updateUsername(User $user, string $newUsername): User
    {
        $newUsername = trim($newUsername);

        if ($newUsername === '') {
            throw new ValidationException('Username is required.');
        }

        $existingUser = $this->userRepository->findOneByUsername($newUsername);
        if ($existingUser !== null && (string) $existingUser->getId() !== (string) $user->getId()) {
            throw new ConflictException('Username already exists.');
        }

        $user->setUsername($newUsername);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $user;
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new ValidationException('Current password is invalid.');
        }

        $passwordError = $this->passwordValidator->validate($newPassword);
        if ($passwordError !== null) {
            throw new ValidationException('New password must be at least 10 characters and include letters, numbers, and symbols.');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
