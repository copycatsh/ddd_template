<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\SameEmailException;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        $user = new User('user-1', new Email('test@example.com'), 'hashed-pwd', UserRole::USER);

        $this->assertEquals('user-1', $user->getId());
        $this->assertTrue($user->getEmail()->equals(new Email('test@example.com')));
        $this->assertEquals('hashed-pwd', $user->getPassword());
        $this->assertSame(UserRole::USER, $user->getRole());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testCreateAdminUser(): void
    {
        $user = new User('user-2', new Email('admin@example.com'), 'hashed-pwd', UserRole::ADMIN);

        $this->assertSame(UserRole::ADMIN, $user->getRole());
        $this->assertEquals(['ROLE_ADMIN'], $user->getRoles());
    }

    public function testChangeEmail(): void
    {
        $user = new User('user-1', new Email('old@example.com'), 'hashed-pwd');
        $newEmail = new Email('new@example.com');

        $user->changeEmail($newEmail);

        $this->assertTrue($user->getEmail()->equals($newEmail));
    }

    public function testChangeEmailToSameEmailThrows(): void
    {
        $user = new User('user-1', new Email('same@example.com'), 'hashed-pwd');

        $this->expectException(SameEmailException::class);
        $this->expectExceptionMessage('New email must be different from current email');

        $user->changeEmail(new Email('same@example.com'));
    }

    public function testDefaultRoleIsUser(): void
    {
        $user = new User('user-1', new Email('test@example.com'), 'hashed-pwd');

        $this->assertSame(UserRole::USER, $user->getRole());
    }
}
