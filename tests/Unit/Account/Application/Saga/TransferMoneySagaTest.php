<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Saga;

use App\Account\Application\Saga\TransferMoneySaga;
use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\Exception\InsufficientFundsException;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use App\Transaction\Domain\Repository\TransactionRepositoryInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class TransferMoneySagaTest extends TestCase
{
    private EventSourcedAccountRepositoryInterface&MockObject $accountRepository;
    private TransactionRepositoryInterface&MockObject $transactionRepository;
    private Connection&MockObject $connection;
    private MessageBusInterface&MockObject $messageBus;
    private TransferMoneySaga $saga;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(EventSourcedAccountRepositoryInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepositoryInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->messageBus->method('dispatch')->willReturnCallback(
            fn (object $message) => new Envelope($message)
        );

        $this->saga = new TransferMoneySaga(
            $this->accountRepository,
            $this->transactionRepository,
            $this->connection,
            $this->messageBus,
        );
    }

    private function createAccountWithBalance(string $id, string $userId, Currency $currency, string $balance): EventSourcedAccount
    {
        $account = EventSourcedAccount::create($id, $userId, $currency);
        $account->markEventsAsCommitted();
        if (bccomp($balance, '0', 2) > 0) {
            $account->deposit(new Money($balance, $currency));
            $account->markEventsAsCommitted();
        }

        return $account;
    }

    public function testExecuteTransfersMoneySuccessfully(): void
    {
        $from = $this->createAccountWithBalance('from-1', 'user-1', Currency::UAH, '500.00');
        $to = $this->createAccountWithBalance('to-1', 'user-2', Currency::UAH, '100.00');

        $this->accountRepository->method('findById')->willReturnMap([
            ['from-1', $from],
            ['to-1', $to],
        ]);

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollBack');

        $this->accountRepository->expects($this->exactly(2))->method('save');
        $this->transactionRepository->expects($this->exactly(2))->method('save');

        $transactionId = $this->saga->execute('from-1', 'to-1', new Money('200.00', Currency::UAH));

        $this->assertNotEmpty($transactionId);
        $this->assertEquals('300.00', $from->getBalance()->getAmount());
        $this->assertEquals('300.00', $to->getBalance()->getAmount());
    }

    public function testExecuteThrowsWhenSameAccount(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot transfer to the same account');

        $this->saga->execute('acc-1', 'acc-1', new Money('100.00', Currency::UAH));
    }

    public function testExecuteThrowsWhenSourceNotFound(): void
    {
        $this->accountRepository->method('findById')->willReturnMap([
            ['from-1', null],
            ['to-1', $this->createAccountWithBalance('to-1', 'user-2', Currency::UAH, '0.00')],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Source account from-1 not found');

        $this->saga->execute('from-1', 'to-1', new Money('100.00', Currency::UAH));
    }

    public function testExecuteThrowsWhenDestinationNotFound(): void
    {
        $this->accountRepository->method('findById')->willReturnMap([
            ['from-1', $this->createAccountWithBalance('from-1', 'user-1', Currency::UAH, '500.00')],
            ['to-1', null],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Destination account to-1 not found');

        $this->saga->execute('from-1', 'to-1', new Money('100.00', Currency::UAH));
    }

    public function testExecuteThrowsWhenCurrencyMismatch(): void
    {
        $from = $this->createAccountWithBalance('from-1', 'user-1', Currency::UAH, '500.00');
        $to = $this->createAccountWithBalance('to-1', 'user-2', Currency::USD, '100.00');

        $this->accountRepository->method('findById')->willReturnMap([
            ['from-1', $from],
            ['to-1', $to],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot transfer between different currencies');

        $this->saga->execute('from-1', 'to-1', new Money('100.00', Currency::UAH));
    }

    public function testExecuteThrowsWhenAmountCurrencyMismatch(): void
    {
        $from = $this->createAccountWithBalance('from-1', 'user-1', Currency::UAH, '500.00');
        $to = $this->createAccountWithBalance('to-1', 'user-2', Currency::UAH, '100.00');

        $this->accountRepository->method('findById')->willReturnMap([
            ['from-1', $from],
            ['to-1', $to],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Amount currency must match account currency');

        $this->saga->execute('from-1', 'to-1', new Money('100.00', Currency::USD));
    }

    public function testExecuteRollsBackOnDepositFailure(): void
    {
        $from = $this->createAccountWithBalance('from-1', 'user-1', Currency::UAH, '500.00');
        $to = $this->createAccountWithBalance('to-1', 'user-2', Currency::UAH, '100.00');

        $this->accountRepository->method('findById')->willReturnMap([
            ['from-1', $from],
            ['to-1', $to],
        ]);

        // First save (from withdraw) succeeds, second save (to deposit) throws
        $saveCallCount = 0;
        $this->accountRepository->method('save')->willReturnCallback(
            function () use (&$saveCallCount): void {
                ++$saveCallCount;
                if (2 === $saveCallCount) {
                    throw new \RuntimeException('Event store write failed');
                }
            }
        );

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->never())->method('commit');
        $this->connection->expects($this->once())->method('rollBack');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transfer failed: Event store write failed');

        $this->saga->execute('from-1', 'to-1', new Money('200.00', Currency::UAH));
    }

    public function testExecuteHandlesInsufficientFunds(): void
    {
        $from = $this->createAccountWithBalance('from-1', 'user-1', Currency::UAH, '50.00');
        $to = $this->createAccountWithBalance('to-1', 'user-2', Currency::UAH, '100.00');

        $this->accountRepository->method('findById')->willReturnMap([
            ['from-1', $from],
            ['to-1', $to],
        ]);

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->never())->method('commit');
        $this->connection->expects($this->once())->method('rollBack');

        $this->expectException(InsufficientFundsException::class);

        $this->saga->execute('from-1', 'to-1', new Money('200.00', Currency::UAH));
    }
}
