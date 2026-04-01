<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserCreatedEvent;
use App\User\Domain\Event\UserEmailChangedEvent;
use App\User\Domain\Exception\SameEmailException;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        $user = User::create('user-1', new Email('test@example.com'), 'hashed-pwd', UserRole::USER);

        $this->assertEquals('user-1', $user->getId());
        $this->assertTrue($user->getEmail()->equals(new Email('test@example.com')));
        $this->assertEquals('hashed-pwd', $user->getPassword());
        $this->assertSame(UserRole::USER, $user->getRole());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testCreateAdminUser(): void
    {
        $user = User::create('user-2', new Email('admin@example.com'), 'hashed-pwd', UserRole::ADMIN);

        $this->assertSame(UserRole::ADMIN, $user->getRole());
        $this->assertEquals(['ROLE_ADMIN'], $user->getRoles());
    }

    public function testChangeEmail(): void
    {
        $user = User::create('user-1', new Email('old@example.com'), 'hashed-pwd');
        $newEmail = new Email('new@example.com');

        $user->changeEmail($newEmail);

        $this->assertTrue($user->getEmail()->equals($newEmail));
    }

    public function testChangeEmailToSameEmailThrows(): void
    {
        $user = User::create('user-1', new Email('same@example.com'), 'hashed-pwd');

        $this->expectException(SameEmailException::class);
        $this->expectExceptionMessage('New email must be different from current email');

        $user->changeEmail(new Email('same@example.com'));
    }

    public function testDefaultRoleIsUser(): void
    {
        $user = User::create('user-1', new Email('test@example.com'), 'hashed-pwd');

        $this->assertSame(UserRole::USER, $user->getRole());
    }

    public function testStaticCreateRecordsUserCreatedEvent(): void
    {
        $user = User::create('user-1', new Email('test@example.com'), 'hashed-pwd', UserRole::ADMIN);

        $events = $user->getUncommittedEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserCreatedEvent::class, $events[0]);
        $this->assertEquals('user-1', $events[0]->getUserId());
        $this->assertEquals('test@example.com', $events[0]->getEmail());
        $this->assertSame(UserRole::ADMIN, $events[0]->getRole());
    }

    public function testStaticCreateSetsCorrectState(): void
    {
        $user = User::create('user-1', new Email('test@example.com'), 'hashed-pwd', UserRole::USER);

        $this->assertEquals('user-1', $user->getId());
        $this->assertTrue($user->getEmail()->equals(new Email('test@example.com')));
        $this->assertEquals('hashed-pwd', $user->getPassword());
        $this->assertSame(UserRole::USER, $user->getRole());
    }

    public function testChangeEmailRecordsUserEmailChangedEvent(): void
    {
        $user = User::create('user-1', new Email('old@example.com'), 'hashed-pwd');
        $user->markEventsAsCommitted();

        $user->changeEmail(new Email('new@example.com'));

        $events = $user->getUncommittedEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserEmailChangedEvent::class, $events[0]);
        $this->assertEquals('user-1', $events[0]->getUserId());
        $this->assertTrue($events[0]->getOldEmail()->equals(new Email('old@example.com')));
        $this->assertTrue($events[0]->getNewEmail()->equals(new Email('new@example.com')));
    }
}
