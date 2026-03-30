<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Put;
use App\Account\Application\Handler\DepositMoneyHandler;
use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use App\Account\Infrastructure\ApiPlatform\Dto\MoneyOperationDto;
use App\Account\Infrastructure\ApiPlatform\Resource\AccountResource;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\DepositMoneyStateProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DepositMoneyStateProcessorTest extends TestCase
{
    private DepositMoneyHandler&MockObject $handler;
    private EventSourcedAccountRepositoryInterface&MockObject $accountRepository;
    private DepositMoneyStateProcessor $processor;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(DepositMoneyHandler::class);
        $this->accountRepository = $this->createMock(EventSourcedAccountRepositoryInterface::class);
        $this->processor = new DepositMoneyStateProcessor($this->handler, $this->accountRepository);
    }

    public function testProcessDepositsAndReturnsResource(): void
    {
        $dto = new MoneyOperationDto();
        $dto->amount = '100.00';
        $dto->currency = 'UAH';

        $account = EventSourcedAccount::create('acc-1', 'user-1', Currency::UAH);
        $account->markEventsAsCommitted();
        $account->deposit(new Money('100.00', Currency::UAH));

        $this->handler->expects($this->once())->method('handle');

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('acc-1')
            ->willReturn($account);

        $result = $this->processor->process($dto, new Put(), ['id' => 'acc-1']);

        $this->assertInstanceOf(AccountResource::class, $result);
        $this->assertEquals('acc-1', $result->id);
        $this->assertEquals('100.00', $result->balance);
    }
}
