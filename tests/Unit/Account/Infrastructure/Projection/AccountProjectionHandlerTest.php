<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Infrastructure\Projection;

use App\Account\Domain\Event\AccountCreatedEvent;
use App\Account\Domain\Event\MoneyDepositedEvent;
use App\Account\Domain\Event\MoneyWithdrawnEvent;
use App\Account\Infrastructure\Projection\AccountProjectionHandler;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccountProjectionHandlerTest extends TestCase
{
    private Connection&MockObject $connection;
    private AccountProjectionHandler $handler;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->handler = new AccountProjectionHandler($this->connection);
    }

    public function testOnAccountCreatedInsertsProjectionRow(): void
    {
        $event = new AccountCreatedEvent('acc-1', 'user-1', Currency::UAH);

        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with('account_projections', $this->callback(function (array $data) {
                return 'acc-1' === $data['id']
                    && 'user-1' === $data['user_id']
                    && 'UAH' === $data['currency']
                    && '0.00' === $data['balance'];
            }));

        $this->handler->onAccountCreated($event);
    }

    public function testOnMoneyDepositedUpdatesBalance(): void
    {
        $event = new MoneyDepositedEvent('acc-1', new Money('100.00', Currency::UAH), '350.00');

        $this->connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'account_projections',
                $this->callback(fn (array $data) => '350.00' === $data['balance']),
                ['id' => 'acc-1']
            )
            ->willReturn(1);

        $this->handler->onMoneyDeposited($event);
    }

    public function testOnMoneyWithdrawnUpdatesBalance(): void
    {
        $event = new MoneyWithdrawnEvent('acc-1', new Money('50.00', Currency::UAH), '200.00');

        $this->connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'account_projections',
                $this->callback(fn (array $data) => '200.00' === $data['balance']),
                ['id' => 'acc-1']
            )
            ->willReturn(1);

        $this->handler->onMoneyWithdrawn($event);
    }

    public function testOnMoneyDepositedThrowsWhenProjectionRowMissing(): void
    {
        $event = new MoneyDepositedEvent('nonexistent', new Money('100.00', Currency::UAH), '100.00');

        $this->connection
            ->expects($this->once())
            ->method('update')
            ->willReturn(0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Projection row missing for account nonexistent');

        $this->handler->onMoneyDeposited($event);
    }

    public function testOnMoneyWithdrawnThrowsWhenProjectionRowMissing(): void
    {
        $event = new MoneyWithdrawnEvent('nonexistent', new Money('50.00', Currency::UAH), '0.00');

        $this->connection
            ->expects($this->once())
            ->method('update')
            ->willReturn(0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Projection row missing for account nonexistent');

        $this->handler->onMoneyWithdrawn($event);
    }
}
