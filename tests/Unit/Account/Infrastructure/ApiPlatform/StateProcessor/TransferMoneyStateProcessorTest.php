<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Put;
use App\Account\Application\Saga\TransferMoneySaga;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Infrastructure\ApiPlatform\Dto\TransferMoneyDto;
use App\Account\Infrastructure\ApiPlatform\Resource\AccountResource;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\TransferMoneyStateProcessor;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransferMoneyStateProcessorTest extends TestCase
{
    private TransferMoneySaga&MockObject $saga;
    private AccountRepositoryInterface&MockObject $accountRepository;
    private TransferMoneyStateProcessor $processor;

    protected function setUp(): void
    {
        $this->saga = $this->createMock(TransferMoneySaga::class);
        $this->accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $this->processor = new TransferMoneyStateProcessor($this->saga, $this->accountRepository);
    }

    public function testProcessDelegatesToSaga(): void
    {
        $dto = new TransferMoneyDto();
        $dto->toAccountId = 'to-1';
        $dto->amount = '100.00';
        $dto->currency = 'UAH';

        $this->saga
            ->expects($this->once())
            ->method('execute')
            ->willReturn('txn-123');

        $account = $this->createMock(Account::class);
        $account->method('getId')->willReturn('from-1');
        $account->method('getUserId')->willReturn('user-1');
        $account->method('getCurrency')->willReturn(Currency::UAH);
        $account->method('getBalance')->willReturn(new Money('9900.00', Currency::UAH));
        $account->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $account->method('getUpdatedAt')->willReturn(new \DateTimeImmutable());

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('from-1')
            ->willReturn($account);

        $result = $this->processor->process($dto, new Put(), ['id' => 'from-1']);

        $this->assertInstanceOf(AccountResource::class, $result);
        $this->assertEquals('from-1', $result->id);
        $this->assertEquals('9900.00', $result->balance);
    }
}
