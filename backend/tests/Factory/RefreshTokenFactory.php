<?php

namespace App\Tests\Factory;

use App\Entity\RefreshToken;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<RefreshToken>
 */
final class RefreshTokenFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return RefreshToken::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'token' => bin2hex(random_bytes(64)),
            'expiresAt' => new \DateTimeImmutable('+30 days'),
            'revoked' => false,
        ];
    }
}
