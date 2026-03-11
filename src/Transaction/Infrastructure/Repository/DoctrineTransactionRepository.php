<?php

namespace App\Transaction\Infrastructure\Repository;

use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\TransactionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineTransactionRepository extends ServiceEntityRepository implements TransactionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function save(Transaction $transaction): void
    {
        $this->getEntityManager()->persist($transaction);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?Transaction
    {
        return $this->find($id);
    }

    public function findByAccountId(string $accountId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.fromAccountId = :accountId')
            ->orWhere('t.toAccountId = :accountId')
            ->setParameter('accountId', $accountId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(TransactionStatus $status): array
    {
        return $this->findBy(
            ['status' => $status],
            ['createdAt' => 'DESC']
        );
    }

    public function findPendingByAccountId(string $accountId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.fromAccountId = :accountId')
            ->orWhere('t.toAccountId = :accountId')
            ->andWhere('t.status = :status')
            ->setParameter('accountId', $accountId)
            ->setParameter('status', TransactionStatus::PENDING)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function delete(Transaction $transaction): void
    {
        $this->getEntityManager()->remove($transaction);
        $this->getEntityManager()->flush();
    }
}
