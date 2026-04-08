<?php

namespace App\Controller\Auth;

use App\Attribute\RateLimited;
use App\Entity\User;
use App\Entity\RefreshToken;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class RefreshController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    #[RateLimited('api_token_refresh', message: 'Too many refresh attempts, please try again later.')]
    public function refresh(Request $request, RefreshTokenRepository $refreshTokenRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->getJsonBody($request);
        $tokenString = (string) ($data['refresh_token'] ?? '');
        if ($tokenString === '') {
            return $this->json(['error' => 'Refresh token is required.'], Response::HTTP_BAD_REQUEST);
        }

        $refreshToken = $refreshTokenRepository->findOneByToken($tokenString);
        if (!$refreshToken instanceof RefreshToken || $refreshToken->isRevoked() || $refreshToken->isExpired()) {
            return $this->json(['error' => 'Invalid or expired refresh token.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $refreshToken->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unable to refresh token.'], Response::HTTP_UNAUTHORIZED);
        }

        // rotate: revoke old refresh token and issue a new one
        $refreshToken->setRevoked(true);

        $newRefreshToken = new RefreshToken();
        $newRefreshToken->setUser($user);
        $newRefreshToken->setToken(bin2hex(random_bytes(64)));
        $newRefreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));
        $newRefreshToken->setRevoked(false);

        $entityManager->persist($refreshToken);
        $entityManager->persist($newRefreshToken);
        $entityManager->flush();

        $accessToken = $this->jwtManager->create($user);

        return $this->json([
            'token' => $accessToken,
            'refresh_token' => $newRefreshToken->getToken(),
            'user' => $this->serializeUser($user),
        ]);
    }

    private function getJsonBody(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => (string) $user->getId(),
            'username' => $user->getUsername(),
            'createdAt' => $user->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}
