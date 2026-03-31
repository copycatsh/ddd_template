<?php

namespace App\Account\Domain\Entity;

use App\Account\Domain\Event\AccountCreatedEvent;
use App\Account\Domain\Event\MoneyDepositedEvent;
use App\Account\Domain\Event\MoneyWithdrawnEvent;
use App\Account\Domain\Exception\InsufficientFundsException;
use App\Shared\Domain\Aggregate\AbstractAggregateRoot;
use App\Shared\Domain\Exception\CurrencyMismatchException;
use App\Shared\Domain\Exception\InvalidAmountException;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;

class Account extends AbstractAggregateRoot
{
    private string $userId;
    private Currency $currency;
    private string $balance;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public static function create(string $accountId, string $userId, Currency $currency): self
    {
        if ('' === trim($userId)) {
            throw new \InvalidArgumentException('User ID cannot be empty');
        }

        $account = new self($accountId);
        $account->recordEvent(new AccountCreatedEvent($accountId, $userId, $currency));

        return $account;
    }

    public function deposit(Money $amount): void
    {
        if (bccomp($amount->getAmount(), '0', 2) <= 0) {
            throw InvalidAmountException::mustBePositive();
        }

        if (!$amount->getCurrency()->equals($this->currency)) {
            throw CurrencyMismatchException::forOperation($this->currency, $amount->getCurrency());
        }

        $newBalance = bcadd($this->balance, $amount->getAmount(), 2);
        $this->recordEvent(new MoneyDepositedEvent($this->getId(), $amount, $newBalance));
    }

    public function withdraw(Money $amount): void
    {
        if (bccomp($amount->getAmount(), '0', 2) <= 0) {
            throw InvalidAmountException::mustBePositive();
        }

        if (!$amount->getCurrency()->equals($this->currency)) {
            throw CurrencyMismatchException::forOperation($this->currency, $amount->getCurrency());
        }

        if (bccomp($this->balance, $amount->getAmount(), 2) < 0) {
            throw InsufficientFundsException::forWithdrawal($this->balance, $amount->getAmount());
        }

        $newBalance = bcsub($this->balance, $amount->getAmount(), 2);
        $this->recordEvent(new MoneyWithdrawnEvent($this->getId(), $amount, $newBalance));
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getBalance(): Money
    {
        return new Money($this->balance, $this->currency);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    protected function applyAccountCreatedEvent(AccountCreatedEvent $event): void
    {
        $this->userId = $event->getUserId();
        $this->currency = $event->getCurrency();
        $this->balance = '0.00';
        $this->createdAt = $event->getOccurredAt();
        $this->updatedAt = $event->getOccurredAt();
    }

    protected function applyMoneyDepositedEvent(MoneyDepositedEvent $event): void
    {
        $this->balance = $event->getNewBalance();
        $this->updatedAt = $event->getOccurredAt();
    }

    protected function applyMoneyWithdrawnEvent(MoneyWithdrawnEvent $event): void
    {
        $this->balance = $event->getNewBalance();
        $this->updatedAt = $event->getOccurredAt();
    }
}
