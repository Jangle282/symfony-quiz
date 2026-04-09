<?php

namespace App\Tests\Factory;

use App\Entity\Game;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Game>
 */
final class GameFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Game::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'createdBy' => UserFactory::new(),
            'difficulty' => DifficultyFactory::new(),
            'totalScore' => 100,
            'startedAt' => new \DateTimeImmutable(),
        ];
    }
}
