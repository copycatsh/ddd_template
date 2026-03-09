# Notification Bounded Context Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a `Notification` bounded context that sends enriched emails to users when transactions are created, completed, or failed.

**Architecture:** Three `#[AsMessageHandler]` classes in `Notification\Application\Handler` listen to `TransactionCreatedEvent`, `TransactionCompletedEvent`, `TransactionFailedEvent` via Symfony Messenger (`sync://`). Each handler resolves user email via `NotificationUserQuery` port and account details via `NotificationAccountQuery` port (both defined in Notification Domain, implemented in Notification Infrastructure using Doctrine). Sent notifications are logged to a `notification_log` table via `NotificationLog` Doctrine entity. The `TransferMoneySaga` dispatches events explicitly via `MessageBusInterface` after each saga step.

**Tech Stack:** Symfony 7, PHP 8.3, Symfony Messenger (`sync://`), Symfony Mailer, Doctrine ORM, PHPUnit

---

## Decisions

- **No Event Sourcing** for Notification — it is a listener/side-effect context, not an aggregate. `NotificationLog` is CRUD only.
- **Two ports in Notification Domain**: `NotificationUserQuery` (get email+name by userId) and `NotificationAccountQuery` (get userId+currency by accountId). No cross-context repository dependencies.
- **Explicit dispatch** in `TransferMoneySaga` via `MessageBusInterface` — no magic/decorator dispatch.
- **`sync://` transport** — same thread, easy to switch to async by changing one line.

---

## Task 1: Configure Messenger

**Files:**
- Modify: `config/packages/messenger.yaml`

**Step 1: Update messenger.yaml**

Replace the file contents with:

```yaml
framework:
    messenger:
        transports:
            sync: 'sync://'

        routing:
            'App\Transaction\Domain\Event\TransactionCreatedEvent': sync
            'App\Transaction\Domain\Event\TransactionCompletedEvent': sync
            'App\Transaction\Domain\Event\TransactionFailedEvent': sync
```

**Step 2: Verify Symfony container compiles**

```bash
docker compose exec php bin/console cache:clear
```

Expected: no errors.

**Step 3: Commit**

```bash
git add config/packages/messenger.yaml
git commit -m "chore: configure Messenger sync transport for transaction events"
```

---

## Task 2: Domain Ports — Query Interfaces and DTOs

**Files:**
- Create: `src/Notification/Domain/Query/NotificationUserData.php`
- Create: `src/Notification/Domain/Query/NotificationUserQuery.php`
- Create: `src/Notification/Domain/Query/NotificationAccountData.php`
- Create: `src/Notification/Domain/Query/NotificationAccountQuery.php`

**Step 1: Create NotificationUserData DTO**

```php
<?php

namespace App\Notification\Domain\Query;

final class NotificationUserData
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
    ) {}
}
```

**Step 2: Create NotificationUserQuery interface (port)**

```php
<?php

namespace App\Notification\Domain\Query;

interface NotificationUserQuery
{
    public function findByUserId(string $userId): ?NotificationUserData;
}
```

**Step 3: Create NotificationAccountData DTO**

```php
<?php

namespace App\Notification\Domain\Query;

final class NotificationAccountData
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $userId,
        public readonly string $currency,
    ) {}
}
```

**Step 4: Create NotificationAccountQuery interface (port)**

```php
<?php

namespace App\Notification\Domain\Query;

interface NotificationAccountQuery
{
    public function findByAccountId(string $accountId): ?NotificationAccountData;
}
```

**Step 5: Commit**

```bash
git add src/Notification/
git commit -m "feat(notification): add domain query ports and DTOs"
```

---

## Task 3: Domain Entity — NotificationLog + Repository Interface

**Files:**
- Create: `src/Notification/Domain/Entity/NotificationLog.php`
- Create: `src/Notification/Domain/Repository/NotificationLogRepositoryInterface.php`

**Step 1: Create NotificationLog entity**

```php
<?php

namespace App\Notification\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notification_log')]
class NotificationLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $transactionId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $accountId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $recipientEmail;

    #[ORM\Column(type: 'string', length: 50)]
    private string $notificationType;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $sentAt;

    public function __construct(
        string $transactionId,
        string $accountId,
        string $userId,
        string $recipientEmail,
        string $notificationType,
    ) {
        $this->transactionId = $transactionId;
        $this->accountId = $accountId;
        $this->userId = $userId;
        $this->recipientEmail = $recipientEmail;
        $this->notificationType = $notificationType;
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTransactionId(): string { return $this->transactionId; }
    public function getAccountId(): string { return $this->accountId; }
    public function getUserId(): string { return $this->userId; }
    public function getRecipientEmail(): string { return $this->recipientEmail; }
    public function getNotificationType(): string { return $this->notificationType; }
    public function getSentAt(): \DateTimeImmutable { return $this->sentAt; }
}
```

**Step 2: Create NotificationLogRepositoryInterface**

```php
<?php

namespace App\Notification\Domain\Repository;

use App\Notification\Domain\Entity\NotificationLog;

interface NotificationLogRepositoryInterface
{
    public function save(NotificationLog $log): void;
}
```

**Step 3: Commit**

```bash
git add src/Notification/Domain/
git commit -m "feat(notification): add NotificationLog entity and repository interface"
```

---

## Task 4: Infrastructure — Doctrine Migration

**Step 1: Generate a blank migration**

```bash
docker compose exec php bin/console doctrine:migrations:generate
```

This creates a new file in `migrations/` with a timestamp, e.g. `migrations/Version20260308000000.php`.

**Step 2: Fill in the migration**

Open the generated file and replace `up()` and `down()` with:

```php
public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE notification_log (
        id BIGINT AUTO_INCREMENT NOT NULL,
        transaction_id VARCHAR(50) NOT NULL,
        account_id VARCHAR(50) NOT NULL,
        user_id VARCHAR(50) NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        notification_type VARCHAR(50) NOT NULL,
        sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
        PRIMARY KEY(id),
        INDEX idx_transaction_id (transaction_id),
        INDEX idx_user_id (user_id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
}

public function down(Schema $schema): void
{
    $this->addSql('DROP TABLE notification_log');
}
```

**Step 3: Run the migration**

```bash
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

Expected: `[OK] Successfully migrated to version: ...`

**Step 4: Commit**

```bash
git add migrations/
git commit -m "feat(notification): add notification_log migration"
```

---

## Task 5: Infrastructure — DoctrineNotificationLogRepository

**Files:**
- Create: `src/Notification/Infrastructure/Repository/DoctrineNotificationLogRepository.php`

**Step 1: Create the repository**

```php
<?php

namespace App\Notification\Infrastructure\Repository;

use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineNotificationLogRepository implements NotificationLogRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function save(NotificationLog $log): void
    {
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
```

**Step 2: Commit**

```bash
git add src/Notification/Infrastructure/Repository/
git commit -m "feat(notification): add DoctrineNotificationLogRepository"
```

---

## Task 6: Infrastructure — DoctrineNotificationUserQuery

**Files:**
- Create: `src/Notification/Infrastructure/Query/DoctrineNotificationUserQuery.php`

**Step 1: Create the adapter**

Queries the `users` table (columns: `id`, `email`). No dependency on `User` domain classes — uses raw DBAL for true isolation.

```php
<?php

namespace App\Notification\Infrastructure\Query;

use App\Notification\Domain\Query\NotificationUserData;
use App\Notification\Domain\Query\NotificationUserQuery;
use Doctrine\DBAL\Connection;

class DoctrineNotificationUserQuery implements NotificationUserQuery
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function findByUserId(string $userId): ?NotificationUserData
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email FROM users WHERE id = :id',
            ['id' => $userId]
        );

        if ($row === false) {
            return null;
        }

        return new NotificationUserData(
            userId: $row['id'],
            email: $row['email'],
        );
    }
}
```

**Step 2: Commit**

```bash
git add src/Notification/Infrastructure/Query/DoctrineNotificationUserQuery.php
git commit -m "feat(notification): add DoctrineNotificationUserQuery adapter"
```

---

## Task 7: Infrastructure — DoctrineNotificationAccountQuery

**Files:**
- Create: `src/Notification/Infrastructure/Query/DoctrineNotificationAccountQuery.php`

**Step 1: Create the adapter**

Queries the `accounts` table (columns: `id`, `user_id`, `currency`).

```php
<?php

namespace App\Notification\Infrastructure\Query;

use App\Notification\Domain\Query\NotificationAccountData;
use App\Notification\Domain\Query\NotificationAccountQuery;
use Doctrine\DBAL\Connection;

class DoctrineNotificationAccountQuery implements NotificationAccountQuery
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function findByAccountId(string $accountId): ?NotificationAccountData
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, user_id, currency FROM accounts WHERE id = :id',
            ['id' => $accountId]
        );

        if ($row === false) {
            return null;
        }

        return new NotificationAccountData(
            accountId: $row['id'],
            userId: $row['user_id'],
            currency: $row['currency'],
        );
    }
}
```

**Step 2: Commit**

```bash
git add src/Notification/Infrastructure/Query/DoctrineNotificationAccountQuery.php
git commit -m "feat(notification): add DoctrineNotificationAccountQuery adapter"
```

---

## Task 8: TDD — TransactionCreatedNotificationHandler

**Files:**
- Create: `tests/Unit/Notification/Application/Handler/TransactionCreatedNotificationHandlerTest.php`
- Create: `src/Notification/Application/Handler/TransactionCreatedNotificationHandler.php`

**Step 1: Write the failing test**

```php
<?php

namespace App\Tests\Unit\Notification\Application\Handler;

use App\Notification\Application\Handler\TransactionCreatedNotificationHandler;
use App\Notification\Domain\Query\NotificationAccountData;
use App\Notification\Domain\Query\NotificationAccountQuery;
use App\Notification\Domain\Query\NotificationUserData;
use App\Notification\Domain\Query\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Transaction\Domain\Event\TransactionCreatedEvent;
use App\Transaction\Domain\ValueObject\TransactionType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TransactionCreatedNotificationHandlerTest extends TestCase
{
    private NotificationUserQuery&MockObject $userQuery;
    private NotificationAccountQuery&MockObject $accountQuery;
    private NotificationLogRepositoryInterface&MockObject $logRepository;
    private MailerInterface&MockObject $mailer;
    private TransactionCreatedNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->userQuery = $this->createMock(NotificationUserQuery::class);
        $this->accountQuery = $this->createMock(NotificationAccountQuery::class);
        $this->logRepository = $this->createMock(NotificationLogRepositoryInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);

        $this->handler = new TransactionCreatedNotificationHandler(
            $this->userQuery,
            $this->accountQuery,
            $this->logRepository,
            $this->mailer,
        );
    }

    public function testSendsEmailAndLogsNotification(): void
    {
        $event = new TransactionCreatedEvent(
            transactionId: 'txn-123',
            accountId: 'acc-456',
            type: TransactionType::TRANSFER,
            amount: '500.00',
            currency: 'UAH',
        );

        $this->accountQuery->expects($this->once())
            ->method('findByAccountId')
            ->with('acc-456')
            ->willReturn(new NotificationAccountData('acc-456', 'usr-789', 'UAH'));

        $this->userQuery->expects($this->once())
            ->method('findByUserId')
            ->with('usr-789')
            ->willReturn(new NotificationUserData('usr-789', 'user@example.com'));

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(fn(Email $email) =>
                in_array('user@example.com', array_map(fn($a) => $a->getAddress(), $email->getTo()))
                && str_contains($email->getSubject(), 'created')
            ));

        $this->logRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn($log) =>
                $log->getTransactionId() === 'txn-123'
                && $log->getNotificationType() === 'transaction_created'
            ));

        ($this->handler)($event);
    }

    public function testSkipsWhenAccountNotFound(): void
    {
        $event = new TransactionCreatedEvent('txn-123', 'acc-missing', TransactionType::TRANSFER, '100.00', 'UAH');

        $this->accountQuery->method('findByAccountId')->willReturn(null);
        $this->mailer->expects($this->never())->method('send');
        $this->logRepository->expects($this->never())->method('save');

        ($this->handler)($event);
    }

    public function testSkipsWhenUserNotFound(): void
    {
        $event = new TransactionCreatedEvent('txn-123', 'acc-456', TransactionType::TRANSFER, '100.00', 'UAH');

        $this->accountQuery->method('findByAccountId')
            ->willReturn(new NotificationAccountData('acc-456', 'usr-missing', 'UAH'));
        $this->userQuery->method('findByUserId')->willReturn(null);

        $this->mailer->expects($this->never())->method('send');
        $this->logRepository->expects($this->never())->method('save');

        ($this->handler)($event);
    }
}
```

**Step 2: Run the test — verify it fails**

```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Notification/Application/Handler/TransactionCreatedNotificationHandlerTest.php --testdox
```

Expected: FAIL — class not found.

**Step 3: Implement the handler**

```php
<?php

namespace App\Notification\Application\Handler;

use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Query\NotificationAccountQuery;
use App\Notification\Domain\Query\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Transaction\Domain\Event\TransactionCreatedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class TransactionCreatedNotificationHandler
{
    public function __construct(
        private readonly NotificationUserQuery $userQuery,
        private readonly NotificationAccountQuery $accountQuery,
        private readonly NotificationLogRepositoryInterface $logRepository,
        private readonly MailerInterface $mailer,
    ) {}

    public function __invoke(TransactionCreatedEvent $event): void
    {
        $account = $this->accountQuery->findByAccountId($event->getAccountId());
        if ($account === null) {
            return;
        }

        $user = $this->userQuery->findByUserId($account->userId);
        if ($user === null) {
            return;
        }

        $email = (new Email())
            ->to($user->email)
            ->subject('Transaction created')
            ->text(sprintf(
                "Your transaction %s has been created.\nAmount: %s %s\nAccount: %s",
                $event->getTransactionId(),
                $event->getAmount(),
                $event->getCurrency(),
                $event->getAccountId(),
            ));

        $this->mailer->send($email);

        $this->logRepository->save(new NotificationLog(
            transactionId: $event->getTransactionId(),
            accountId: $event->getAccountId(),
            userId: $account->userId,
            recipientEmail: $user->email,
            notificationType: 'transaction_created',
        ));
    }
}
```

**Step 4: Run the test — verify it passes**

```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Notification/Application/Handler/TransactionCreatedNotificationHandlerTest.php --testdox
```

Expected: All 3 tests PASS.

**Step 5: Commit**

```bash
git add src/Notification/Application/Handler/TransactionCreatedNotificationHandler.php \
        tests/Unit/Notification/Application/Handler/TransactionCreatedNotificationHandlerTest.php
git commit -m "feat(notification): add TransactionCreatedNotificationHandler with tests"
```

---

## Task 9: TDD — TransactionCompletedNotificationHandler

**Files:**
- Create: `tests/Unit/Notification/Application/Handler/TransactionCompletedNotificationHandlerTest.php`
- Create: `src/Notification/Application/Handler/TransactionCompletedNotificationHandler.php`

**Step 1: Write the failing test**

```php
<?php

namespace App\Tests\Unit\Notification\Application\Handler;

use App\Notification\Application\Handler\TransactionCompletedNotificationHandler;
use App\Notification\Domain\Query\NotificationAccountData;
use App\Notification\Domain\Query\NotificationAccountQuery;
use App\Notification\Domain\Query\NotificationUserData;
use App\Notification\Domain\Query\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Transaction\Domain\Event\TransactionCompletedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TransactionCompletedNotificationHandlerTest extends TestCase
{
    private NotificationUserQuery&MockObject $userQuery;
    private NotificationAccountQuery&MockObject $accountQuery;
    private NotificationLogRepositoryInterface&MockObject $logRepository;
    private MailerInterface&MockObject $mailer;
    private TransactionCompletedNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->userQuery = $this->createMock(NotificationUserQuery::class);
        $this->accountQuery = $this->createMock(NotificationAccountQuery::class);
        $this->logRepository = $this->createMock(NotificationLogRepositoryInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);

        $this->handler = new TransactionCompletedNotificationHandler(
            $this->userQuery,
            $this->accountQuery,
            $this->logRepository,
            $this->mailer,
        );
    }

    public function testSendsEmailAndLogsNotification(): void
    {
        $event = new TransactionCompletedEvent('txn-123', 'acc-456');

        $this->accountQuery->expects($this->once())
            ->method('findByAccountId')
            ->with('acc-456')
            ->willReturn(new NotificationAccountData('acc-456', 'usr-789', 'UAH'));

        $this->userQuery->expects($this->once())
            ->method('findByUserId')
            ->with('usr-789')
            ->willReturn(new NotificationUserData('usr-789', 'user@example.com'));

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(fn(Email $email) =>
                in_array('user@example.com', array_map(fn($a) => $a->getAddress(), $email->getTo()))
                && str_contains($email->getSubject(), 'completed')
            ));

        $this->logRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn($log) =>
                $log->getTransactionId() === 'txn-123'
                && $log->getNotificationType() === 'transaction_completed'
            ));

        ($this->handler)($event);
    }

    public function testSkipsWhenAccountNotFound(): void
    {
        $event = new TransactionCompletedEvent('txn-123', 'acc-missing');
        $this->accountQuery->method('findByAccountId')->willReturn(null);
        $this->mailer->expects($this->never())->method('send');
        ($this->handler)($event);
    }
}
```

**Step 2: Run the test — verify it fails**

```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Notification/Application/Handler/TransactionCompletedNotificationHandlerTest.php --testdox
```

Expected: FAIL — class not found.

**Step 3: Implement the handler**

```php
<?php

namespace App\Notification\Application\Handler;

use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Query\NotificationAccountQuery;
use App\Notification\Domain\Query\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Transaction\Domain\Event\TransactionCompletedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class TransactionCompletedNotificationHandler
{
    public function __construct(
        private readonly NotificationUserQuery $userQuery,
        private readonly NotificationAccountQuery $accountQuery,
        private readonly NotificationLogRepositoryInterface $logRepository,
        private readonly MailerInterface $mailer,
    ) {}

    public function __invoke(TransactionCompletedEvent $event): void
    {
        $account = $this->accountQuery->findByAccountId($event->getAccountId());
        if ($account === null) {
            return;
        }

        $user = $this->userQuery->findByUserId($account->userId);
        if ($user === null) {
            return;
        }

        $email = (new Email())
            ->to($user->email)
            ->subject('Transaction completed')
            ->text(sprintf(
                "Your transaction %s has been completed successfully.\nAccount: %s (%s)",
                $event->getTransactionId(),
                $event->getAccountId(),
                $account->currency,
            ));

        $this->mailer->send($email);

        $this->logRepository->save(new NotificationLog(
            transactionId: $event->getTransactionId(),
            accountId: $event->getAccountId(),
            userId: $account->userId,
            recipientEmail: $user->email,
            notificationType: 'transaction_completed',
        ));
    }
}
```

**Step 4: Run the test — verify it passes**

```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Notification/Application/Handler/TransactionCompletedNotificationHandlerTest.php --testdox
```

Expected: All tests PASS.

**Step 5: Commit**

```bash
git add src/Notification/Application/Handler/TransactionCompletedNotificationHandler.php \
        tests/Unit/Notification/Application/Handler/TransactionCompletedNotificationHandlerTest.php
git commit -m "feat(notification): add TransactionCompletedNotificationHandler with tests"
```

---

## Task 10: TDD — TransactionFailedNotificationHandler

**Files:**
- Create: `tests/Unit/Notification/Application/Handler/TransactionFailedNotificationHandlerTest.php`
- Create: `src/Notification/Application/Handler/TransactionFailedNotificationHandler.php`

**Step 1: Write the failing test**

```php
<?php

namespace App\Tests\Unit\Notification\Application\Handler;

use App\Notification\Application\Handler\TransactionFailedNotificationHandler;
use App\Notification\Domain\Query\NotificationAccountData;
use App\Notification\Domain\Query\NotificationAccountQuery;
use App\Notification\Domain\Query\NotificationUserData;
use App\Notification\Domain\Query\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Transaction\Domain\Event\TransactionFailedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TransactionFailedNotificationHandlerTest extends TestCase
{
    private NotificationUserQuery&MockObject $userQuery;
    private NotificationAccountQuery&MockObject $accountQuery;
    private NotificationLogRepositoryInterface&MockObject $logRepository;
    private MailerInterface&MockObject $mailer;
    private TransactionFailedNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->userQuery = $this->createMock(NotificationUserQuery::class);
        $this->accountQuery = $this->createMock(NotificationAccountQuery::class);
        $this->logRepository = $this->createMock(NotificationLogRepositoryInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);

        $this->handler = new TransactionFailedNotificationHandler(
            $this->userQuery,
            $this->accountQuery,
            $this->logRepository,
            $this->mailer,
        );
    }

    public function testSendsEmailWithReasonAndLogsNotification(): void
    {
        $event = new TransactionFailedEvent('txn-123', 'acc-456', 'Insufficient funds');

        $this->accountQuery->method('findByAccountId')
            ->willReturn(new NotificationAccountData('acc-456', 'usr-789', 'UAH'));
        $this->userQuery->method('findByUserId')
            ->willReturn(new NotificationUserData('usr-789', 'user@example.com'));

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(fn(Email $email) =>
                in_array('user@example.com', array_map(fn($a) => $a->getAddress(), $email->getTo()))
                && str_contains($email->getSubject(), 'failed')
                && str_contains($email->getTextBody(), 'Insufficient funds')
            ));

        $this->logRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn($log) =>
                $log->getNotificationType() === 'transaction_failed'
            ));

        ($this->handler)($event);
    }

    public function testSendsEmailWithoutReasonWhenReasonIsNull(): void
    {
        $event = new TransactionFailedEvent('txn-123', 'acc-456', null);

        $this->accountQuery->method('findByAccountId')
            ->willReturn(new NotificationAccountData('acc-456', 'usr-789', 'UAH'));
        $this->userQuery->method('findByUserId')
            ->willReturn(new NotificationUserData('usr-789', 'user@example.com'));

        $this->mailer->expects($this->once())->method('send');
        $this->logRepository->expects($this->once())->method('save');

        ($this->handler)($event);
    }

    public function testSkipsWhenAccountNotFound(): void
    {
        $event = new TransactionFailedEvent('txn-123', 'acc-missing', null);
        $this->accountQuery->method('findByAccountId')->willReturn(null);
        $this->mailer->expects($this->never())->method('send');
        ($this->handler)($event);
    }
}
```

**Step 2: Run the test — verify it fails**

```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Notification/Application/Handler/TransactionFailedNotificationHandlerTest.php --testdox
```

Expected: FAIL — class not found.

**Step 3: Implement the handler**

```php
<?php

namespace App\Notification\Application\Handler;

use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Query\NotificationAccountQuery;
use App\Notification\Domain\Query\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Transaction\Domain\Event\TransactionFailedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class TransactionFailedNotificationHandler
{
    public function __construct(
        private readonly NotificationUserQuery $userQuery,
        private readonly NotificationAccountQuery $accountQuery,
        private readonly NotificationLogRepositoryInterface $logRepository,
        private readonly MailerInterface $mailer,
    ) {}

    public function __invoke(TransactionFailedEvent $event): void
    {
        $account = $this->accountQuery->findByAccountId($event->getAccountId());
        if ($account === null) {
            return;
        }

        $user = $this->userQuery->findByUserId($account->userId);
        if ($user === null) {
            return;
        }

        $body = sprintf(
            "Your transaction %s has failed.\nAccount: %s (%s)",
            $event->getTransactionId(),
            $event->getAccountId(),
            $account->currency,
        );

        if ($event->getReason() !== null) {
            $body .= "\nReason: " . $event->getReason();
        }

        $email = (new Email())
            ->to($user->email)
            ->subject('Transaction failed')
            ->text($body);

        $this->mailer->send($email);

        $this->logRepository->save(new NotificationLog(
            transactionId: $event->getTransactionId(),
            accountId: $event->getAccountId(),
            userId: $account->userId,
            recipientEmail: $user->email,
            notificationType: 'transaction_failed',
        ));
    }
}
```

**Step 4: Run the test — verify it passes**

```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Notification/Application/Handler/TransactionFailedNotificationHandlerTest.php --testdox
```

Expected: All 3 tests PASS.

**Step 5: Commit**

```bash
git add src/Notification/Application/Handler/TransactionFailedNotificationHandler.php \
        tests/Unit/Notification/Application/Handler/TransactionFailedNotificationHandlerTest.php
git commit -m "feat(notification): add TransactionFailedNotificationHandler with tests"
```

---

## Task 11: Wire Dispatch in TransferMoneySaga

**Files:**
- Modify: `src/Account/Application/Saga/TransferMoneySaga.php`

The saga must dispatch the three transaction events via `MessageBusInterface` after each step.

**Step 1: Inject MessageBusInterface into TransferMoneySaga**

Add to constructor:

```php
use Symfony\Component\Messenger\MessageBusInterface;

public function __construct(
    private readonly AccountRepositoryInterface $accountRepository,
    private readonly TransactionRepositoryInterface $transactionRepository,
    private readonly EntityManagerInterface $entityManager,
    private readonly MessageBusInterface $messageBus,  // add this
) {}
```

**Step 2: Dispatch TransactionCreatedEvent after step 1**

After `$this->transactionRepository->save($transaction);` (first save), add:

```php
use App\Transaction\Domain\Event\TransactionCreatedEvent;

$this->messageBus->dispatch(new TransactionCreatedEvent(
    $transactionId,
    $fromAccountId,
    TransactionType::TRANSFER,
    $amount->getAmount(),
    $amount->getCurrency()->value,
));
```

**Step 3: Dispatch TransactionCompletedEvent after step 4**

After `$transaction->complete(); $this->transactionRepository->save($transaction);`, before `$this->entityManager->commit();`, add:

```php
use App\Transaction\Domain\Event\TransactionCompletedEvent;

$this->messageBus->dispatch(new TransactionCompletedEvent($transactionId, $fromAccountId));
```

**Step 4: Dispatch TransactionFailedEvent in the catch block**

Inside the `catch (\Exception $e)` block, before `$this->entityManager->rollback();`, add:

```php
use App\Transaction\Domain\Event\TransactionFailedEvent;

$this->messageBus->dispatch(new TransactionFailedEvent($transactionId, $fromAccountId, $e->getMessage()));
```

Note: `$transactionId` is declared before the try block — it is in scope inside catch.

**Step 5: Verify container compiles**

```bash
docker compose exec php bin/console cache:clear
```

Expected: no errors.

**Step 6: Run all unit tests**

```bash
docker compose exec php vendor/bin/phpunit tests/Unit/ --testdox
```

Expected: All tests PASS.

**Step 7: Commit**

```bash
git add src/Account/Application/Saga/TransferMoneySaga.php
git commit -m "feat(notification): dispatch transaction events from TransferMoneySaga"
```

---

## Task 12: Run Full Test Suite

**Step 1: Run all tests**

```bash
docker compose exec php vendor/bin/phpunit --testdox
```

Expected: All tests PASS. No regressions.

**Step 2: Verify static analysis**

```bash
docker compose exec php vendor/bin/phpstan analyse src/ --level=6
```

Fix any reported type errors before merging.

**Step 3: Final commit (if any fixups)**

```bash
git add -p
git commit -m "fix(notification): address static analysis findings"
```

---

## Final File Tree

```
src/Notification/
├── Domain/
│   ├── Entity/
│   │   └── NotificationLog.php
│   ├── Query/
│   │   ├── NotificationAccountData.php
│   │   ├── NotificationAccountQuery.php   ← port (interface)
│   │   ├── NotificationUserData.php
│   │   └── NotificationUserQuery.php      ← port (interface)
│   └── Repository/
│       └── NotificationLogRepositoryInterface.php
├── Application/
│   └── Handler/
│       ├── TransactionCreatedNotificationHandler.php
│       ├── TransactionCompletedNotificationHandler.php
│       └── TransactionFailedNotificationHandler.php
└── Infrastructure/
    ├── Query/
    │   ├── DoctrineNotificationAccountQuery.php  ← adapter
    │   └── DoctrineNotificationUserQuery.php     ← adapter
    └── Repository/
        └── DoctrineNotificationLogRepository.php

tests/Unit/Notification/Application/Handler/
├── TransactionCreatedNotificationHandlerTest.php
├── TransactionCompletedNotificationHandlerTest.php
└── TransactionFailedNotificationHandlerTest.php
```

**Modified files:**
- `config/packages/messenger.yaml` — sync transport + routing
- `src/Account/Application/Saga/TransferMoneySaga.php` — inject bus, dispatch events
- `migrations/Version<timestamp>.php` — notification_log table
