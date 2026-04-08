<?php

namespace App\Tests;

use App\Tests\Trait\AuthenticatesUsers;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    use AuthenticatesUsers;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }
}
