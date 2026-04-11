<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Exception\ConflictException;
use App\Exception\ValidationException;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface $entityManager,
        private RefreshTokenRepository $refreshTokenRepository,
        private PasswordValidator $passwordValidator,
    ) {
    }

    public function register(string $username, string $password): User
    {
        $username = trim($username);

        if ($username === '') {
            throw new ValidationException('Username is required.');
        }

        $passwordError = $this->passwordValidator->validate($password);
        if ($passwordError !== null) {
            throw new ValidationException($passwordError);
        }

        if ($this->userRepository->findOneByUsername($username) !== null) {
            throw new ConflictException('Username already exists.');
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function login(string $username, string $password): array
    {
        $username = trim($username);

        $user = $this->userRepository->findOneByUsername($username);
        if (!$user instanceof User || !$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
        }

        $token = $this->jwtManager->create($user);
        $refreshToken = $this->createRefreshToken($user);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken->getToken(),
            'user' => $user,
        ];
    }

    public function refresh(string $refreshTokenString): array
    {
        if ($refreshTokenString === '') {
            throw new ValidationException('Refresh token is required.');
        }

        $refreshToken = $this->refreshTokenRepository->findOneByToken($refreshTokenString);
        if (!$refreshToken instanceof RefreshToken || $refreshToken->isRevoked() || $refreshToken->isExpired()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired refresh token.');
        }

        $user = $refreshToken->getUser();
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Unable to refresh token.');
        }

        // Rotate: revoke old, create new
        $refreshToken->setRevoked(true);
        $this->entityManager->persist($refreshToken);

        $newRefreshToken = $this->createRefreshToken($user);
        $accessToken = $this->jwtManager->create($user);

        return [
            'token' => $accessToken,
            'refresh_token' => $newRefreshToken->getToken(),
            'user' => $user,
        ];
    }

    public function logout(User $user): void
    {
        $this->refreshTokenRepository->revokeAllForUser($user);
    }

    private function createRefreshToken(User $user): RefreshToken
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setToken(bin2hex(random_bytes(64)));
        $refreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));
        $refreshToken->setRevoked(false);

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $refreshToken;
    }
}
