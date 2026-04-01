<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Handler;

use App\User\Application\Command\DeleteUserCommand;
use App\User\Application\Handler\DeleteUserHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteUserHandlerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private DeleteUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new DeleteUserHandler($this->userRepository);
    }

    public function testHandleDeletesUser(): void
    {
        $user = new User('user-1', new Email('test@example.com'), 'hashed-pwd', UserRole::USER);
        $command = new DeleteUserCommand('user-1');

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('user-1')
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('delete')
            ->with($user);

        $this->handler->handle($command);
    }

    public function testHandleThrowsWhenUserNotFound(): void
    {
        $command = new DeleteUserCommand('nonexistent');

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->handler->handle($command);
    }
}
