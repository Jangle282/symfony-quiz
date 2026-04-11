<?php

namespace App\Tests\Factory;

use App\Entity\UserGame;
use App\Entity\UserGameRole;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<UserGame>
 */
final class UserGameFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return UserGame::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'game' => GameFactory::new(),
            'role' => UserGameRole::Host,
        ];
    }
}
