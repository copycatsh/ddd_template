<?php

namespace App\Notification\Application\Handler;

use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Port\NotificationAccountQuery;
use App\Notification\Domain\Port\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Shared\Integration\Event\TransactionCreatedIntegrationEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class TransactionCreatedNotificationHandler
{
    public function __construct(
        private NotificationAccountQuery $accountQuery,
        private NotificationUserQuery $userQuery,
        private NotificationLogRepositoryInterface $logRepository,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(TransactionCreatedIntegrationEvent $event): void
    {
        $account = $this->accountQuery->findByAccountId($event->accountId);
        if (null === $account) {
            return;
        }

        $user = $this->userQuery->findByUserId($account->userId);
        if (null === $user) {
            return;
        }

        $email = (new Email())
            ->to($user->email)
            ->subject('Transaction created')
            ->text(sprintf(
                'Transaction %s created: %s %s',
                $event->transactionId,
                $event->amount,
                $event->currency,
            ));

        $this->mailer->send($email);

        $log = new NotificationLog(
            $event->transactionId,
            $event->accountId,
            $account->userId,
            $user->email,
            NotificationType::TransactionCreated,
        );

        $this->logRepository->save($log);
    }
}
