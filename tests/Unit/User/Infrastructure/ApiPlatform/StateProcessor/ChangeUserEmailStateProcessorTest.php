<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Put;
use App\User\Application\Handler\ChangeUserEmailHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use App\User\Infrastructure\ApiPlatform\Dto\ChangeUserEmailDto;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;
use App\User\Infrastructure\ApiPlatform\StateProcessor\ChangeUserEmailStateProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeUserEmailStateProcessorTest extends TestCase
{
    private ChangeUserEmailHandler&MockObject $handler;
    private UserRepositoryInterface&MockObject $userRepository;
    private ChangeUserEmailStateProcessor $processor;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(ChangeUserEmailHandler::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->processor = new ChangeUserEmailStateProcessor($this->handler, $this->userRepository);
    }

    public function testProcessChangesEmailAndReturnsResource(): void
    {
        $dto = new ChangeUserEmailDto();
        $dto->email = 'new@example.com';

        $updatedUser = User::create('user-1', new Email('new@example.com'), 'hashed', UserRole::USER);

        $this->handler
            ->expects($this->once())
            ->method('handle');

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('user-1')
            ->willReturn($updatedUser);

        $result = $this->processor->process($dto, new Put(), ['id' => 'user-1']);

        $this->assertInstanceOf(UserResource::class, $result);
        $this->assertEquals('new@example.com', $result->email);
    }
}
