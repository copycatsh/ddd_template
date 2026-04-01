<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Handler;

use App\User\Application\Command\ChangeUserEmailCommand;
use App\User\Application\Handler\ChangeUserEmailHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeUserEmailHandlerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private ChangeUserEmailHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new ChangeUserEmailHandler($this->userRepository);
    }

    public function testHandleChangesEmail(): void
    {
        $user = User::create('user-1', new Email('old@example.com'), 'hashed-pwd', UserRole::USER);
        $newEmail = new Email('new@example.com');
        $command = new ChangeUserEmailCommand('user-1', $newEmail);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('user-1')
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('new@example.com')
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->handler->handle($command);

        $this->assertTrue($user->getEmail()->equals($newEmail));
    }

    public function testHandleThrowsWhenUserNotFound(): void
    {
        $command = new ChangeUserEmailCommand('nonexistent', new Email('new@example.com'));

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testHandleThrowsWhenEmailAlreadyTaken(): void
    {
        $existingUser = User::create('user-2', new Email('taken@example.com'), 'hashed-pwd');
        $user = User::create('user-1', new Email('old@example.com'), 'hashed-pwd');
        $command = new ChangeUserEmailCommand('user-1', new Email('taken@example.com'));

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('user-1')
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('taken@example.com')
            ->willReturn($existingUser);

        $this->expectException(UserAlreadyExistsException::class);

        $this->handler->handle($command);
    }
}
