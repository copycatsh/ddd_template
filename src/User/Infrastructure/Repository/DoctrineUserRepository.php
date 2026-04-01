<?php

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\MessageBusInterface;

class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private MessageBusInterface $messageBus,
    ) {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $events = $user->getUncommittedEvents();

        $this->getEntityManager()->getConnection()->beginTransaction();

        try {
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();

            foreach ($events as $event) {
                $this->messageBus->dispatch($event);
            }

            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            throw $e;
        }

        $user->markEventsAsCommitted();
    }

    public function findById(string $id): ?User
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function delete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}
