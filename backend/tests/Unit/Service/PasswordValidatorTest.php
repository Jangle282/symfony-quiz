<?php

namespace App\Tests\Unit\Service;

use App\Service\PasswordValidator;
use PHPUnit\Framework\TestCase;

class PasswordValidatorTest extends TestCase
{
    private PasswordValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PasswordValidator();
    }

    public function testValidPasswordReturnsNull(): void
    {
        $this->assertNull($this->validator->validate('Str0ngP@ss!'));
    }

    public function testTooShortPasswordReturnsError(): void
    {
        $this->assertNotNull($this->validator->validate('Ab1!'));
    }

    public function testPasswordWithoutLettersReturnsError(): void
    {
        $this->assertNotNull($this->validator->validate('1234567890!'));
    }

    public function testPasswordWithoutDigitsReturnsError(): void
    {
        $this->assertNotNull($this->validator->validate('abcdefghij!'));
    }

    public function testPasswordWithoutSymbolsReturnsError(): void
    {
        $this->assertNotNull($this->validator->validate('abcdefghij1'));
    }

    public function testExactly10CharsValidPasswordReturnsNull(): void
    {
        $this->assertNull($this->validator->validate('Abcdefgh1!'));
    }
}
