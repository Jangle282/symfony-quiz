<?php

namespace App\Tests\Factory;

use App\Entity\Answer;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Answer>
 */
final class AnswerFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Answer::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'question' => QuestionFactory::new(),
            'answerText' => self::faker()->sentence(),
            'isCorrect' => false,
            'userSelected' => false,
        ];
    }
}
