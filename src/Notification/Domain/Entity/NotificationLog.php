<?php

namespace App\Notification\Domain\Entity;

use App\Notification\Domain\ValueObject\NotificationType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notification_log')]
class NotificationLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
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

    #[ORM\Column(type: 'string', length: 30, enumType: NotificationType::class)]
    private NotificationType $notificationType;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $sentAt;

    public function __construct(
        string $transactionId,
        string $accountId,
        string $userId,
        string $recipientEmail,
        NotificationType $notificationType,
    ) {
        $this->transactionId = $transactionId;
        $this->accountId = $accountId;
        $this->userId = $userId;
        $this->recipientEmail = $recipientEmail;
        $this->notificationType = $notificationType;
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function getNotificationType(): NotificationType
    {
        return $this->notificationType;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }
}
