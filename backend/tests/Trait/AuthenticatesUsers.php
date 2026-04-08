<?php

namespace App\Tests\Trait;

use App\Tests\Factory\RefreshTokenFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

trait AuthenticatesUsers
{
    protected function generateToken(UserInterface $user): string
    {
        return self::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }

    /**
     * @return array{token: string, refresh_token: string}
     */
    protected function authenticateUser(UserInterface $user): array
    {
        $token = $this->generateToken($user);
        $refreshToken = RefreshTokenFactory::createOne(['user' => $user]);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken->getToken(),
        ];
    }
}
