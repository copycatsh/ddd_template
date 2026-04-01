<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Security;

use App\User\Domain\Entity\User;
use App\User\Domain\Port\PasswordHasherInterface;
use App\User\Infrastructure\Security\SymfonyPasswordHasher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

class SymfonyPasswordHasherTest extends TestCase
{
    public function testHashReturnsHashedString(): void
    {
        $factory = new PasswordHasherFactory([
            User::class => ['algorithm' => 'auto'],
        ]);
        $hasher = new SymfonyPasswordHasher($factory);

        $hashed = $hasher->hash('plaintext123');

        $this->assertNotEmpty($hashed);
        $this->assertNotEquals('plaintext123', $hashed);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
    }
}
