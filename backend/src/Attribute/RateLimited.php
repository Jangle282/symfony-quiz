<?php

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RateLimited
{
    public function __construct(
        public readonly string $limiter,
        public readonly string $message = 'Too many requests, please try again later.',
    ) {
    }
}
