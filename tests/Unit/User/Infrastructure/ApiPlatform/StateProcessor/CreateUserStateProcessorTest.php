<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Post;
use App\User\Application\Handler\CreateUserHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use App\User\Infrastructure\ApiPlatform\Dto\CreateUserDto;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;
use App\User\Infrastructure\ApiPlatform\StateProcessor\CreateUserStateProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUserStateProcessorTest extends TestCase
{
    private CreateUserHandler&MockObject $handler;
    private UserRepositoryInterface&MockObject $userRepository;
    private CreateUserStateProcessor $processor;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(CreateUserHandler::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->processor = new CreateUserStateProcessor($this->handler, $this->userRepository);
    }

    public function testProcessCreatesUserAndReturnsResource(): void
    {
        $dto = new CreateUserDto();
        $dto->email = 'test@example.com';
        $dto->password = 'password123';
        $dto->role = 'USER';

        $user = new User('user-1', new Email('test@example.com'), 'hashed', UserRole::USER);

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn('user-1');

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('user-1')
            ->willReturn($user);

        $result = $this->processor->process($dto, new Post());

        $this->assertInstanceOf(UserResource::class, $result);
        $this->assertEquals('user-1', $result->id);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals('ROLE_USER', $result->role);
    }
}
