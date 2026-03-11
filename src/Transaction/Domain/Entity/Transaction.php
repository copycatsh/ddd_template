<?php

namespace App\Transaction\Domain\Entity;

use App\Account\Domain\ValueObject\Money;
use App\Transaction\Domain\Exception\TransactionAlreadyCompletedException;
use App\Transaction\Domain\ValueObject\TransactionStatus;
use App\Transaction\Domain\ValueObject\TransactionType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'transactions')]
class Transaction
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $fromAccountId;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $toAccountId;

    #[ORM\Column(type: 'string', length: 20, enumType: TransactionType::class)]
    private TransactionType $type;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'string', length: 20, enumType: TransactionStatus::class)]
    private TransactionStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct(
        string $id,
        string $fromAccountId,
        ?string $toAccountId,
        TransactionType $type,
        Money $amount,
    ) {
        $this->id = $id;
        $this->fromAccountId = $fromAccountId;
        $this->toAccountId = $toAccountId;
        $this->type = $type;
        $this->amount = $amount->getAmount();
        $this->currency = $amount->getCurrency()->value;
        $this->status = TransactionStatus::PENDING;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        if (!$this->status->isPending()) {
            throw TransactionAlreadyCompletedException::forTransaction($this->id, $this->status);
        }

        $this->status = TransactionStatus::COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function fail(): void
    {
        if (!$this->status->isPending()) {
            throw TransactionAlreadyCompletedException::forTransaction($this->id, $this->status);
        }

        $this->status = TransactionStatus::FAILED;
        $this->completedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getFromAccountId(): string
    {
        return $this->fromAccountId;
    }

    public function getToAccountId(): ?string
    {
        return $this->toAccountId;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): TransactionStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }
}
