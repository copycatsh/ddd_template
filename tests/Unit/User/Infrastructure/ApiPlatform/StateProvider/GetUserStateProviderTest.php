<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\ApiPlatform\StateProvider;

use ApiPlatform\Metadata\Get;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;
use App\User\Infrastructure\ApiPlatform\StateProvider\GetUserStateProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetUserStateProviderTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private GetUserStateProvider $provider;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->provider = new GetUserStateProvider($this->userRepository);
    }

    public function testProvideReturnsUserResource(): void
    {
        $user = new User('user-1', new Email('test@example.com'), 'hashed', UserRole::USER);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('user-1')
            ->willReturn($user);

        $result = $this->provider->provide(new Get(), ['id' => 'user-1']);

        $this->assertInstanceOf(UserResource::class, $result);
        $this->assertEquals('user-1', $result->id);
        $this->assertEquals('test@example.com', $result->email);
    }

    public function testProvideThrowsWhenUserNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->provider->provide(new Get(), ['id' => 'nonexistent']);
    }
}
