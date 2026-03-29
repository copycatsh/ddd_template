<?php

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\Exception\AccountAlreadyExistsException;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineAccountRepository extends ServiceEntityRepository implements AccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function save(Account $account): void
    {
        try {
            $this->getEntityManager()->persist($account);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException) {
            throw AccountAlreadyExistsException::forUserAndCurrency($account->getUserId(), $account->getCurrency());
        }
    }

    public function findById(string $id): ?Account
    {
        return $this->find($id);
    }

    public function findByUserIdAndCurrency(string $userId, Currency $currency): ?Account
    {
        return $this->findOneBy([
            'userId' => $userId,
            'currency' => $currency,
        ]);
    }

    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId]);
    }
}
