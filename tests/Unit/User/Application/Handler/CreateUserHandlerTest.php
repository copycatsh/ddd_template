<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Handler;

use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Handler\CreateUserHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserHandlerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private CreateUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->handler = new CreateUserHandler($this->userRepository, $this->passwordHasher);
    }

    public function testHandleCreatesUserAndReturnsId(): void
    {
        $command = new CreateUserCommand('test@example.com', 'password123', UserRole::USER);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn(null);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed-password');

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $userId = $this->handler->handle($command);

        $this->assertNotEmpty($userId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $userId
        );
    }

    public function testHandleThrowsWhenUserAlreadyExists(): void
    {
        $existingUser = new User('existing-id', new Email('test@example.com'), 'hashed');
        $command = new CreateUserCommand('test@example.com', 'password123');

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($existingUser);

        $this->expectException(UserAlreadyExistsException::class);

        $this->handler->handle($command);
    }
}
