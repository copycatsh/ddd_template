<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Handler;

use App\Notification\Application\Handler\TransactionFailedNotificationHandler;
use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Port\NotificationAccountData;
use App\Notification\Domain\Port\NotificationAccountQuery;
use App\Notification\Domain\Port\NotificationUserData;
use App\Notification\Domain\Port\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Transaction\Domain\Event\TransactionFailedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[CoversClass(TransactionFailedNotificationHandler::class)]
final class TransactionFailedNotificationHandlerTest extends TestCase
{
    private NotificationAccountQuery&MockObject $accountQuery;
    private NotificationUserQuery&MockObject $userQuery;
    private NotificationLogRepositoryInterface&MockObject $logRepository;
    private MailerInterface&MockObject $mailer;
    private TransactionFailedNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->accountQuery = $this->createMock(NotificationAccountQuery::class);
        $this->userQuery = $this->createMock(NotificationUserQuery::class);
        $this->logRepository = $this->createMock(NotificationLogRepositoryInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);

        $this->handler = new TransactionFailedNotificationHandler(
            $this->accountQuery,
            $this->userQuery,
            $this->logRepository,
            $this->mailer,
        );
    }

    #[Test]
    public function testSendsEmailWithReasonAndLogsNotification(): void
    {
        $transactionId = 'txn-123';
        $accountId = 'acc-456';
        $userId = 'user-789';
        $recipientEmail = 'user@example.com';
        $reason = 'Insufficient funds';

        $event = new TransactionFailedEvent(
            $transactionId,
            $accountId,
            $reason,
        );

        $accountData = new NotificationAccountData($accountId, $userId, 'USD');
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
            ->with(self::callback(function (Email $email) use ($recipientEmail, $transactionId, $reason) {
                return $email->getTo()[0]->getAddress() === $recipientEmail
                    && 'Transaction failed' === $email->getSubject()
                    && str_contains($email->getTextBody(), $transactionId)
                    && str_contains($email->getTextBody(), $reason);
            }));

        $this->logRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (NotificationLog $log) use ($transactionId) {
                return $log->getTransactionId() === $transactionId
                    && NotificationType::TransactionFailed === $log->getNotificationType();
            }));

        ($this->handler)($event);
    }

    #[Test]
    public function testSendsEmailWithoutReasonWhenNull(): void
    {
        $transactionId = 'txn-123';
        $accountId = 'acc-456';
        $userId = 'user-789';
        $recipientEmail = 'user@example.com';

        $event = new TransactionFailedEvent(
            $transactionId,
            $accountId,
            null,
        );

        $accountData = new NotificationAccountData($accountId, $userId, 'USD');
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
            ->with(self::callback(function (Email $email) use ($recipientEmail, $transactionId) {
                $body = $email->getTextBody();

                return $email->getTo()[0]->getAddress() === $recipientEmail
                    && 'Transaction failed' === $email->getSubject()
                    && str_contains($body, $transactionId)
                    && !str_contains($body, 'Reason:');
            }));

        $this->logRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (NotificationLog $log) use ($transactionId) {
                return $log->getTransactionId() === $transactionId
                    && NotificationType::TransactionFailed === $log->getNotificationType();
            }));

        ($this->handler)($event);
    }

    #[Test]
    public function testSkipsWhenAccountNotFound(): void
    {
        $event = new TransactionFailedEvent(
            'txn-123',
            'acc-456',
            'Some reason',
        );

        $this->accountQuery
            ->expects(self::once())
            ->method('findByAccountId')
            ->with('acc-456')
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
        $accountId = 'acc-456';
        $userId = 'user-789';

        $event = new TransactionFailedEvent(
            'txn-123',
            $accountId,
            'Some reason',
        );

        $accountData = new NotificationAccountData($accountId, $userId, 'USD');

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
