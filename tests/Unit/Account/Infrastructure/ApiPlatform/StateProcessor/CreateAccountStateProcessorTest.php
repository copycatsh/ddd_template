<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Post;
use App\Account\Application\Handler\CreateAccountHandler;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Infrastructure\ApiPlatform\Dto\CreateAccountDto;
use App\Account\Infrastructure\ApiPlatform\Resource\AccountResource;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\CreateAccountStateProcessor;
use App\Shared\Domain\ValueObject\Currency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateAccountStateProcessorTest extends TestCase
{
    private CreateAccountHandler&MockObject $handler;
    private AccountRepositoryInterface&MockObject $accountRepository;
    private CreateAccountStateProcessor $processor;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(CreateAccountHandler::class);
        $this->accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $this->processor = new CreateAccountStateProcessor($this->handler, $this->accountRepository);
    }

    public function testProcessCreatesAccountAndReturnsResource(): void
    {
        $dto = new CreateAccountDto();
        $dto->userId = 'user-1';
        $dto->currency = 'UAH';

        $account = Account::create('acc-1', 'user-1', Currency::UAH);

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn('acc-1');

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('acc-1')
            ->willReturn($account);

        $result = $this->processor->process($dto, new Post());

        $this->assertInstanceOf(AccountResource::class, $result);
        $this->assertEquals('acc-1', $result->id);
        $this->assertEquals('user-1', $result->userId);
        $this->assertSame(Currency::UAH, $result->currency);
        $this->assertEquals('0.00', $result->balance);
    }
}
