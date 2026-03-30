<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\Projection;

use App\Account\Domain\Event\AccountCreatedEvent;
use App\Account\Domain\Event\MoneyDepositedEvent;
use App\Account\Domain\Event\MoneyWithdrawnEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

class AccountProjectionHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[AsMessageHandler]
    public function onAccountCreated(AccountCreatedEvent $event): void
    {
        try {
            $this->connection->insert('account_projections', [
                'id' => $event->getAccountId(),
                'user_id' => $event->getUserId(),
                'currency' => $event->getCurrency()->value,
                'balance' => '0.00',
                'created_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
                'updated_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Idempotent: projection already exists, safe to skip during replay
        }
    }

    #[AsMessageHandler]
    public function onMoneyDeposited(MoneyDepositedEvent $event): void
    {
        $this->updateBalance($event->getAccountId(), $event->getNewBalance(), $event->getOccurredAt());
    }

    #[AsMessageHandler]
    public function onMoneyWithdrawn(MoneyWithdrawnEvent $event): void
    {
        $this->updateBalance($event->getAccountId(), $event->getNewBalance(), $event->getOccurredAt());
    }

    private function updateBalance(string $accountId, string $newBalance, \DateTimeImmutable $occurredAt): void
    {
        $affected = $this->connection->update('account_projections', [
            'balance' => $newBalance,
            'updated_at' => $occurredAt->format('Y-m-d H:i:s'),
        ], ['id' => $accountId]);

        if (0 === $affected) {
            throw new \RuntimeException("Projection row missing for account {$accountId}");
        }
    }
}
