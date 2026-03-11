<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Handler;

use App\Notification\Application\Handler\TransactionCreatedNotificationHandler;
use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Port\NotificationAccountData;
use App\Notification\Domain\Port\NotificationAccountQuery;
use App\Notification\Domain\Port\NotificationUserData;
use App\Notification\Domain\Port\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Transaction\Domain\Event\TransactionCreatedEvent;
use App\Transaction\Domain\ValueObject\TransactionType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[CoversClass(TransactionCreatedNotificationHandler::class)]
final class TransactionCreatedNotificationHandlerTest extends TestCase
{
    private NotificationAccountQuery&MockObject $accountQuery;
    private NotificationUserQuery&MockObject $userQuery;
    private NotificationLogRepositoryInterface&MockObject $logRepository;
    private MailerInterface&MockObject $mailer;
    private TransactionCreatedNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->accountQuery = $this->createMock(NotificationAccountQuery::class);
        $this->userQuery = $this->createMock(NotificationUserQuery::class);
        $this->logRepository = $this->createMock(NotificationLogRepositoryInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);

        $this->handler = new TransactionCreatedNotificationHandler(
            $this->accountQuery,
            $this->userQuery,
            $this->logRepository,
            $this->mailer,
        );
    }

    #[Test]
    public function testSendsEmailAndLogsNotification(): void
    {
        $transactionId = 'txn-123';
        $accountId = 'acc-456';
        $userId = 'user-789';
        $recipientEmail = 'user@example.com';
        $amount = '100.00';
        $currency = 'USD';

        $event = new TransactionCreatedEvent(
            $transactionId,
            $accountId,
            TransactionType::DEPOSIT,
            $amount,
            $currency,
        );

        $accountData = new NotificationAccountData($accountId, $userId, $currency);
        $userData = new NotificationUserData($userId, $recipientEmail);

        $this->accountQuery
            ->expects(self::once())
            ->method('findByAccountId')
            ->with($accountId)
            ->willReturn($accountData);

        $this->userQuery
            ->expects(self::once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($userData);

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(function (Email $email) use ($recipientEmail) {
                return $email->getTo()[0]->getAddress() === $recipientEmail;
            }));

        $this->logRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (NotificationLog $log) use ($transactionId) {
                return $log->getTransactionId() === $transactionId
                    && NotificationType::TransactionCreated === $log->getNotificationType();
            }));

        ($this->handler)($event);
    }

    #[Test]
    public function testSkipsWhenAccountNotFound(): void
    {
        $transactionId = 'txn-123';
        $accountId = 'acc-456';
        $amount = '100.00';
        $currency = 'USD';

        $event = new TransactionCreatedEvent(
            $transactionId,
            $accountId,
            TransactionType::DEPOSIT,
            $amount,
            $currency,
        );

        $this->accountQuery
            ->expects(self::once())
            ->method('findByAccountId')
            ->with($accountId)
            ->willReturn(null);

        $this->userQuery
            ->expects(self::never())
            ->method('findByUserId');

        $this->mailer
            ->expects(self::never())
            ->method('send');

        $this->logRepository
            ->expects(self::never())
            ->method('save');

        ($this->handler)($event);
    }

    #[Test]
    public function testSkipsWhenUserNotFound(): void
    {
        $transactionId = 'txn-123';
        $accountId = 'acc-456';
        $userId = 'user-789';
        $amount = '100.00';
        $currency = 'USD';

        $event = new TransactionCreatedEvent(
            $transactionId,
            $accountId,
            TransactionType::DEPOSIT,
            $amount,
            $currency,
        );

        $accountData = new NotificationAccountData($accountId, $userId, $currency);

        $this->accountQuery
            ->expects(self::once())
            ->method('findByAccountId')
            ->with($accountId)
            ->willReturn($accountData);

        $this->userQuery
            ->expects(self::once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn(null);

        $this->mailer
            ->expects(self::never())
            ->method('send');

        $this->logRepository
            ->expects(self::never())
            ->method('save');

        ($this->handler)($event);
    }
}
