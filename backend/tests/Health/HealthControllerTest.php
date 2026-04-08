<?php

namespace App\Tests\Health;

use App\Tests\ApiTestCase;

class HealthControllerTest extends ApiTestCase
{
    public function setUp(): void  
    {
        parent::setUp();
    }

    public function testHealthEndpointReturnsOk(): void
    {
        $this->client->request('GET', '/api/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertSame('ok', $data['status']);
        $this->assertArrayHasKey('timestamp', $data);
    }
}
