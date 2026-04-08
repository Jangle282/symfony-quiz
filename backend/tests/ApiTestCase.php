<?php

namespace App\Tests;

use App\Tests\Trait\AuthenticatesUsers;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;

abstract class ApiTestCase extends WebTestCase
{
    use AuthenticatesUsers;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function consumeRateLimiter(string $name, int $tokens, string $key = '127.0.0.1'): void
    {
        /** @var RateLimiterFactory $factory */
        $factory = self::getContainer()->get('limiter.' . $name);
        $factory->create($key)->consume($tokens);
    }

    protected function resetRateLimiter(string $name, string $key = '127.0.0.1'): void
    {
        /** @var RateLimiterFactory $factory */
        $factory = self::getContainer()->get('limiter.' . $name);
        $factory->create($key)->reset();
    }
}
