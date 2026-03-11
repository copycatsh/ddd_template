<?php

namespace App\Tests\Unit\Account\Domain\Entity;

use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\Event\AccountCreatedEvent;
use App\Account\Domain\Event\MoneyDepositedEvent;
use App\Account\Domain\Event\MoneyWithdrawnEvent;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class EventSourcedAccountTest extends TestCase
{
    public function testAccountCreation()
    {
        $account = EventSourcedAccount::create('test-id', 'user-id', Currency::UAH);
        $this->assertEquals(0.00, $account->getBalance()->getAmount());
        $this->assertEquals(Currency::UAH, $account->getBalance()->getCurrency());
        $this->assertEquals('user-id', $account->getUserId());
    }

    public function testDepositRecordsEvent()
    {
        $account = EventSourcedAccount::create('test-id', 'user-id', Currency::UAH);
        $account->markEventsAsCommitted();

        $money = new Money('100.50', Currency::UAH);
        $account->deposit($money);

        $this->assertEquals('100.50', $account->getBalance()->getAmount());

        $events = $account->getUncommittedEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(MoneyDepositedEvent::class, $events[0]);

        $this->assertEquals(2, $account->getVersion());
    }

    public function testWithdrawRecordsEvent()
    {
        $account = EventSourcedAccount::create('test-id', 'user-id', Currency::UAH);
        $account->markEventsAsCommitted();

        $money1 = new Money('100.50', Currency::UAH);
        $money2 = new Money('30', Currency::UAH);
        $account->deposit($money1);

        $this->assertEquals('100.50', $account->getBalance()->getAmount());

        $account->withdraw($money2);
        $this->assertEquals('70.50', $account->getBalance()->getAmount());

        $events = $account->getUncommittedEvents();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(MoneyDepositedEvent::class, $events[0]);
        $this->assertInstanceOf(MoneyWithdrawnEvent::class, $events[1]);

        $this->assertEquals('30', $events[1]->getAmount()->getAmount());
        $this->assertEquals('70.50', $events[1]->getNewBalance());

        $this->assertEquals(3, $account->getVersion());
    }

    public function testAccountCreationRecordsEvent(): void
    {
        $account = EventSourcedAccount::create('test-id', 'user-id', Currency::UAH);
        $events = $account->getUncommittedEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(AccountCreatedEvent::class, $events[0]);
        $this->assertEquals(1, $account->getVersion());
    }

    public function testReconstitute(): void
    {
        $events = [
            new AccountCreatedEvent('test-id', 'user-id', Currency::UAH),
            new MoneyDepositedEvent('test-id', new Money('100.00', Currency::UAH), '100.00'),
        ];

        $account = EventSourcedAccount::reconstitute('test-id', $events);

        $this->assertEquals('100.00', $account->getBalance()->getAmount());
        $this->assertEquals(2, $account->getVersion());
        $this->assertCount(0, $account->getUncommittedEvents()); // reconstitute не створює uncommitted events
    }
}
