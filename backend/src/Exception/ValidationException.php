<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidationException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(400, $message);
    }
}
