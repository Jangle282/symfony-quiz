<?php

namespace App\Tests\Factory;

use App\Entity\Difficulty;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Difficulty>
 */
final class DifficultyFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Difficulty::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => 'medium',
        ];
    }
}
