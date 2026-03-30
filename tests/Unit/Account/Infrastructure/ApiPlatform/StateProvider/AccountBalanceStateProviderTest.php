<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Infrastructure\ApiPlatform\StateProvider;

use ApiPlatform\Metadata\Get;
use App\Account\Application\Handler\GetAccountBalanceHandler;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Infrastructure\ApiPlatform\StateProvider\AccountBalanceStateProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccountBalanceStateProviderTest extends TestCase
{
    private GetAccountBalanceHandler&MockObject $handler;
    private AccountBalanceStateProvider $provider;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(GetAccountBalanceHandler::class);
        $this->provider = new AccountBalanceStateProvider($this->handler);
    }

    public function testProvideReturnsBalanceResponse(): void
    {
        $response = new AccountBalanceResponse('acc-1', '100.00', 'UAH', new \DateTimeImmutable());

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $result = $this->provider->provide(new Get(), ['id' => 'acc-1']);

        $this->assertInstanceOf(AccountBalanceResponse::class, $result);
        $this->assertEquals('acc-1', $result->accountId);
    }

    public function testProvideReturnsNullWhenNotFound(): void
    {
        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn(null);

        $result = $this->provider->provide(new Get(), ['id' => 'nonexistent']);

        $this->assertNull($result);
    }
}
