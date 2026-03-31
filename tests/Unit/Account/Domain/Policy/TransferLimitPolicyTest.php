<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Domain\Policy;

use App\Account\Domain\Exception\TransferLimitExceededException;
use App\Account\Domain\Policy\TransferLimitPolicy;
use App\Account\Domain\Port\TransferActivityData;
use App\Account\Domain\Port\TransferActivityQuery;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class TransferLimitPolicyTest extends TestCase
{
    private const string DEFAULT_ACCOUNT_ID = '550e8400-e29b-41d4-a716-446655440000';
    private const string DEFAULT_LIMIT = '10000.00';

    public function testUnderLimitDoesNotThrow(): void
    {
        $policy = $this->createPolicy('5000.00', self::DEFAULT_LIMIT);

        $policy->enforce(self::DEFAULT_ACCOUNT_ID, new Money('4000.00', Currency::UAH));

        $this->addToAssertionCount(1);
    }

    public function testExactlyAtLimitDoesNotThrow(): void
    {
        $policy = $this->createPolicy('9900.00', self::DEFAULT_LIMIT);

        $policy->enforce(self::DEFAULT_ACCOUNT_ID, new Money('100.00', Currency::UAH));

        $this->addToAssertionCount(1);
    }

    public function testOverLimitThrows(): void
    {
        $policy = $this->createPolicy('9900.00', self::DEFAULT_LIMIT);

        $this->expectException(TransferLimitExceededException::class);
        $this->expectExceptionMessage('Daily transfer limit exceeded');

        $policy->enforce(self::DEFAULT_ACCOUNT_ID, new Money('100.01', Currency::UAH));
    }

    public function testFirstTransferOfDayWithZeroActivity(): void
    {
        $policy = $this->createPolicy('0.00', self::DEFAULT_LIMIT);

        $policy->enforce(self::DEFAULT_ACCOUNT_ID, new Money('5000.00', Currency::UAH));

        $this->addToAssertionCount(1);
    }

    public function testCustomLimitValue(): void
    {
        $policy = $this->createPolicy('400.00', '500.00');

        $this->expectException(TransferLimitExceededException::class);
        $this->expectExceptionMessage('Daily transfer limit exceeded');

        $policy->enforce(self::DEFAULT_ACCOUNT_ID, new Money('100.01', Currency::UAH));
    }

    public function testFirstTransferExceedingFullLimitThrows(): void
    {
        $policy = $this->createPolicy('0.00', self::DEFAULT_LIMIT);

        $this->expectException(TransferLimitExceededException::class);
        $this->expectExceptionMessage('Daily transfer limit exceeded');

        $policy->enforce(self::DEFAULT_ACCOUNT_ID, new Money('10000.01', Currency::UAH));
    }

    private function createPolicy(string $dailyTotal, string $limit): TransferLimitPolicy
    {
        $activityQuery = $this->createMock(TransferActivityQuery::class);
        $activityQuery->method('getDailyActivity')
            ->willReturn(new TransferActivityData(
                dailyTotal: $dailyTotal,
                dailyCount: 1,
                date: new \DateTimeImmutable('today', new \DateTimeZone('UTC')),
            ));

        return new TransferLimitPolicy($activityQuery, $limit);
    }
}
