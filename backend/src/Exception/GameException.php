<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class GameException extends HttpException
{
    public static function invalidDifficulty(): self
    {
        return new self(400, 'Invalid difficulty. Valid values: easy, medium, hard.');
    }

    public static function categoryNotFound(): self
    {
        return new self(500, 'Category "General Knowledge" not found. Please seed the database.');
    }

    public static function alreadyCompleted(): self
    {
        return new self(400, 'Game is already completed.');
    }

    public static function notFound(): self
    {
        return new self(404, 'Game not found.');
    }
}
