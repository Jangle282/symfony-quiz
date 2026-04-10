<?php

namespace App\Tests\Factory;

use App\Entity\Round;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Round>
 */
final class RoundFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Round::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'game' => GameFactory::new(),
            'category' => CategoryFactory::new(),
            'roundNumber' => 1,
        ];
    }
}
