<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Put;
use App\Account\Application\Handler\WithdrawMoneyHandler;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use App\Account\Infrastructure\ApiPlatform\Dto\MoneyOperationDto;
use App\Account\Infrastructure\ApiPlatform\Resource\AccountResource;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\WithdrawMoneyStateProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WithdrawMoneyStateProcessorTest extends TestCase
{
    private WithdrawMoneyHandler&MockObject $handler;
    private AccountRepositoryInterface&MockObject $accountRepository;
    private WithdrawMoneyStateProcessor $processor;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(WithdrawMoneyHandler::class);
        $this->accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $this->processor = new WithdrawMoneyStateProcessor($this->handler, $this->accountRepository);
    }

    public function testProcessWithdrawsAndReturnsResource(): void
    {
        $dto = new MoneyOperationDto();
        $dto->amount = '50.00';
        $dto->currency = 'UAH';

        $account = Account::create('acc-1', 'user-1', Currency::UAH);
        $account->markEventsAsCommitted();
        $account->deposit(new Money('200.00', Currency::UAH));
        $account->markEventsAsCommitted();
        $account->withdraw(new Money('50.00', Currency::UAH));

        $this->handler->expects($this->once())->method('handle');

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('acc-1')
            ->willReturn($account);

        $result = $this->processor->process($dto, new Put(), ['id' => 'acc-1']);

        $this->assertInstanceOf(AccountResource::class, $result);
        $this->assertEquals('acc-1', $result->id);
        $this->assertEquals('150.00', $result->balance);
    }
}
