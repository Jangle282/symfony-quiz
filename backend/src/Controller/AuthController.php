<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        #[Autowire(service: 'limiter.api_login')] private RateLimiterFactory $loginLimiter,
        #[Autowire(service: 'limiter.api_registration')] private RateLimiterFactory $registrationLimiter,
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = $this->getJsonBody($request);

        $limiter = $this->registrationLimiter->create($request->getClientIp() ?? 'anonymous');
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Too many registration attempts, please try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '') {
            return $this->json(['error' => 'Username is required.'], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            return $this->json(['error' => 'Password must be at least 10 characters and include letters, numbers, and symbols.'], Response::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneByUsername($username) !== null) {
            return $this->json(['error' => 'Username already exists.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json($this->serializeUser($user), Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository
    ): JsonResponse {
        $data = $this->getJsonBody($request);

        $limiter = $this->loginLimiter->create($request->getClientIp() ?? 'anonymous');
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Too many login attempts, please try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $user = $userRepository->findOneByUsername($username);
        if (!$user instanceof User || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->json([], Response::HTTP_NO_CONTENT);
    }

    #[Route('/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refresh(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unable to refresh token.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'token' => $token,
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
