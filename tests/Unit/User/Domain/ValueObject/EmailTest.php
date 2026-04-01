<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testValidEmail(): void
    {
        $email = new Email('Test@Example.COM');

        $this->assertEquals('test@example.com', $email->getValue());
        $this->assertEquals('test@example.com', (string) $email);
    }

    public function testInvalidEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email('not-an-email');
    }

    public function testEmptyEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email('');
    }

    public function testEquals(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('TEST@EXAMPLE.COM');
        $email3 = new Email('other@example.com');

        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }
}
