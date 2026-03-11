<?php

namespace App\Notification\Application\Handler;

use App\Notification\Domain\Entity\NotificationLog;
use App\Notification\Domain\Port\NotificationAccountQuery;
use App\Notification\Domain\Port\NotificationUserQuery;
use App\Notification\Domain\Repository\NotificationLogRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Transaction\Domain\Event\TransactionCompletedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class TransactionCompletedNotificationHandler
{
    public function __construct(
        private NotificationAccountQuery $accountQuery,
        private NotificationUserQuery $userQuery,
        private NotificationLogRepositoryInterface $logRepository,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(TransactionCompletedEvent $event): void
    {
        $account = $this->accountQuery->findByAccountId($event->getAccountId());
        if (null === $account) {
            return;
        }

        $user = $this->userQuery->findByUserId($account->userId);
        if (null === $user) {
            return;
        }

        $email = (new Email())
            ->to($user->email)
            ->subject('Transaction completed')
            ->text(sprintf(
                'Transaction %s completed successfully.',
                $event->getTransactionId(),
            ));

        $this->mailer->send($email);

        $log = new NotificationLog(
            $event->getTransactionId(),
            $event->getAccountId(),
            $account->userId,
            $user->email,
            NotificationType::TransactionCompleted,
        );

        $this->logRepository->save($log);
    }
}
