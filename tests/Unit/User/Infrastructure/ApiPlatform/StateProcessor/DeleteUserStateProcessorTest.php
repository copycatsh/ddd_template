<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Delete;
use App\Account\Application\Handler\GetUserAccountsHandler;
use App\User\Domain\Exception\UserHasActiveAccountsException;
use App\Account\Application\Query\Response\AccountSummary;
use App\Account\Application\Query\Response\UserAccountsResponse;
use App\User\Application\Handler\DeleteUserHandler;
use App\User\Infrastructure\ApiPlatform\StateProcessor\DeleteUserStateProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteUserStateProcessorTest extends TestCase
{
    private DeleteUserHandler&MockObject $handler;
    private GetUserAccountsHandler&MockObject $accountsHandler;
    private DeleteUserStateProcessor $processor;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(DeleteUserHandler::class);
        $this->accountsHandler = $this->createMock(GetUserAccountsHandler::class);
        $this->processor = new DeleteUserStateProcessor($this->handler, $this->accountsHandler);
    }

    public function testProcessDeletesUserWithNoAccounts(): void
    {
        $response = new UserAccountsResponse('user-1', []);

        $this->accountsHandler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $this->handler
            ->expects($this->once())
            ->method('handle');

        $result = $this->processor->process(null, new Delete(), ['id' => 'user-1']);

        $this->assertNull($result);
    }

    public function testProcessThrowsWhenUserHasAccounts(): void
    {
        $summary = new AccountSummary('acc-1', '100.00', 'UAH', new \DateTimeImmutable());
        $response = new UserAccountsResponse('user-1', [$summary]);

        $this->accountsHandler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $this->handler
            ->expects($this->never())
            ->method('handle');

        $this->expectException(UserHasActiveAccountsException::class);
        $this->expectExceptionMessage('Cannot delete user');

        $this->processor->process(null, new Delete(), ['id' => 'user-1']);
    }
}
