<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserCreatedEvent;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use App\User\Infrastructure\Repository\DoctrineUserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class DoctrineUserRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private MessageBusInterface&MockObject $messageBus;
    private Connection&MockObject $connection;
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $classMetadata = new ClassMetadata(User::class);

        $this->entityManager
            ->method('getClassMetadata')
            ->with(User::class)
            ->willReturn($classMetadata);

        $this->entityManager
            ->method('getConnection')
            ->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        $this->repository = new DoctrineUserRepository($registry, $this->messageBus);
    }

    public function testSaveDispatchesCollectedEvents(): void
    {
        $user = User::create('user-1', new Email('test@example.com'), 'hashed', UserRole::USER);

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollBack');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UserCreatedEvent::class))
            ->willReturn(new Envelope(new \stdClass()));

        $this->repository->save($user);

        $this->assertEmpty($user->getUncommittedEvents());
    }

    public function testSaveWithNoEventsDoesNotDispatch(): void
    {
        $user = User::create('user-1', new Email('test@example.com'), 'hashed', UserRole::USER);
        $user->markEventsAsCommitted();

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('commit');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->repository->save($user);
    }

    public function testSaveRollsBackOnDispatchFailure(): void
    {
        $user = User::create('user-1', new Email('test@example.com'), 'hashed', UserRole::USER);

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->never())->method('commit');
        $this->connection->expects($this->once())->method('rollBack');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('Handler failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Handler failed');

        try {
            $this->repository->save($user);
        } catch (\RuntimeException $e) {
            $this->assertNotEmpty($user->getUncommittedEvents(), 'Events should survive failed save for retry');
            throw $e;
        }
    }
}
