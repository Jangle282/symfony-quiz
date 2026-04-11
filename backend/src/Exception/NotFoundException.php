<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class NotFoundException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(404, $message);
    }
}
