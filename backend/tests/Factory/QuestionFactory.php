<?php

namespace App\Tests\Factory;

use App\Entity\Question;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Question>
 */
final class QuestionFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Question::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'round' => RoundFactory::new(),
            'questionText' => self::faker()->sentence() . '?',
        ];
    }
}
