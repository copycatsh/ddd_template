<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Domain\Service;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\Exception\TransferLimitExceededException;
use App\Account\Domain\Exception\TransferValidationException;
use App\Account\Domain\Policy\TransferLimitPolicyInterface;
use App\Account\Domain\Service\MoneyTransferDomainService;
use App\Account\Domain\Specification\SpecificationInterface;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MoneyTransferDomainServiceTest extends TestCase
{
    private SpecificationInterface&MockObject $specification;
    private TransferLimitPolicyInterface&MockObject $policy;
    private MoneyTransferDomainService $service;

    protected function setUp(): void
    {
        $this->specification = $this->createMock(SpecificationInterface::class);
        $this->policy = $this->createMock(TransferLimitPolicyInterface::class);
        $this->service = new MoneyTransferDomainService($this->specification, $this->policy);
    }

    private function createAccount(string $id, string $userId, Currency $currency, string $balance): Account
    {
        $account = Account::create($id, $userId, $currency);
        $account->markEventsAsCommitted();
        if (bccomp($balance, '0', 2) > 0) {
            $account->deposit(new Money($balance, $currency));
            $account->markEventsAsCommitted();
        }

        return $account;
    }

    public function testValidatePassesWhenAllRulesSatisfied(): void
    {
        $from = $this->createAccount('from-1', 'user-1', Currency::UAH, '500.00');
        $to = $this->createAccount('to-1', 'user-2', Currency::UAH, '100.00');
        $amount = new Money('200.00', Currency::UAH);

        $this->specification->method('isSatisfiedBy')->willReturn(true);

        $this->service->validate($from, $to, $amount);
        $this->addToAssertionCount(1);
    }

    public function testValidateThrowsWhenSpecificationFails(): void
    {
        $from = $this->createAccount('from-1', 'user-1', Currency::UAH, '500.00');
        $to = $this->createAccount('to-1', 'user-2', Currency::UAH, '100.00');
        $amount = new Money('200.00', Currency::UAH);

        $this->specification->method('isSatisfiedBy')->willReturn(false);
        $this->specification->method('reason')->willReturn('Cannot transfer to the same account');

        $this->policy->expects($this->never())->method('enforce');

        $this->expectException(TransferValidationException::class);
        $this->expectExceptionMessage('Cannot transfer to the same account');

        $this->service->validate($from, $to, $amount);
    }

    public function testValidateThrowsWhenPolicyFails(): void
    {
        $from = $this->createAccount('from-1', 'user-1', Currency::UAH, '500.00');
        $to = $this->createAccount('to-1', 'user-2', Currency::UAH, '100.00');
        $amount = new Money('200.00', Currency::UAH);

        $this->specification->method('isSatisfiedBy')->willReturn(true);
        $this->policy->method('enforce')->willThrowException(
            TransferLimitExceededException::dailyLimitExceeded('from-1', '9500.00', '200.00', '10000.00')
        );

        $this->expectException(TransferLimitExceededException::class);

        $this->service->validate($from, $to, $amount);
    }
}
