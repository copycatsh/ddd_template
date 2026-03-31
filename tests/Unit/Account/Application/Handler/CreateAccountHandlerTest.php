<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Application\Handler\CreateAccountHandler;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Exception\AccountAlreadyExistsException;
use App\Account\Domain\Port\AccountProjectionData;
use App\Account\Domain\Port\AccountProjectionQuery;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Shared\Domain\ValueObject\Currency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateAccountHandlerTest extends TestCase
{
    private AccountRepositoryInterface&MockObject $accountRepository;
    private AccountProjectionQuery&MockObject $projectionQuery;
    private CreateAccountHandler $handler;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $this->projectionQuery = $this->createMock(AccountProjectionQuery::class);
        $this->handler = new CreateAccountHandler($this->accountRepository, $this->projectionQuery);
    }

    public function testHandleCreatesAccountAndReturnsId(): void
    {
        $command = new CreateAccountCommand('user-123', Currency::UAH);

        $this->projectionQuery
            ->expects($this->once())
            ->method('findByUserIdAndCurrency')
            ->with('user-123', 'UAH')
            ->willReturn(null);

        $this->accountRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Account::class));

        $accountId = $this->handler->handle($command);

        $this->assertNotEmpty($accountId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $accountId
        );
    }

    public function testHandleThrowsWhenAccountAlreadyExists(): void
    {
        $command = new CreateAccountCommand('user-123', Currency::UAH);

        $this->projectionQuery
            ->expects($this->once())
            ->method('findByUserIdAndCurrency')
            ->with('user-123', 'UAH')
            ->willReturn(new AccountProjectionData(
                'existing-id', 'user-123', 'UAH', '0.00',
                new \DateTimeImmutable(), new \DateTimeImmutable(),
            ));

        $this->accountRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(AccountAlreadyExistsException::class);
        $this->expectExceptionMessage('Account already exists for user user-123 with currency UAH');

        $this->handler->handle($command);
    }
}
