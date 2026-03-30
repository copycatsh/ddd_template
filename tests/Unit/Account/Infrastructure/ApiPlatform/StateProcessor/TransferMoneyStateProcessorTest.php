<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Put;
use App\Account\Application\Saga\TransferMoneySaga;
use App\Account\Infrastructure\ApiPlatform\Dto\TransferMoneyDto;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\TransferMoneyStateProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransferMoneyStateProcessorTest extends TestCase
{
    private TransferMoneySaga&MockObject $saga;
    private TransferMoneyStateProcessor $processor;

    protected function setUp(): void
    {
        $this->saga = $this->createMock(TransferMoneySaga::class);
        $this->processor = new TransferMoneyStateProcessor($this->saga);
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

        $result = $this->processor->process($dto, new Put(), ['id' => 'from-1']);

        $this->assertEquals('txn-123', $result['transactionId']);
        $this->assertEquals('from-1', $result['fromAccountId']);
        $this->assertEquals('to-1', $result['toAccountId']);
        $this->assertTrue($result['success']);
    }
}
