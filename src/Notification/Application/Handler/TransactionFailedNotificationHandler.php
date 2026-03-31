<?php

namespace App\Notification\Application\Handler;

use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Port\NotificationAccountQuery;
use App\Notification\Domain\Port\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Shared\Integration\Event\TransactionFailedIntegrationEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class TransactionFailedNotificationHandler
{
    public function __construct(
        private NotificationAccountQuery $accountQuery,
        private NotificationUserQuery $userQuery,
        private NotificationLogRepositoryInterface $logRepository,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(TransactionFailedIntegrationEvent $event): void
    {
        $account = $this->accountQuery->findByAccountId($event->accountId);
        if (null === $account) {
            return;
        }

        $user = $this->userQuery->findByUserId($account->userId);
        if (null === $user) {
            return;
        }

        $body = sprintf('Transaction %s failed.', $event->transactionId);
        if (null !== $event->reason) {
            $body .= sprintf(' Reason: %s', $event->reason);
        }

        $email = (new Email())
            ->to($user->email)
            ->subject('Transaction failed')
            ->text($body);

        $this->mailer->send($email);

        $log = new NotificationLog(
            $event->transactionId,
            $event->accountId,
            $account->userId,
            $user->email,
            NotificationType::TransactionFailed,
        );

        $this->logRepository->save($log);
    }
}
