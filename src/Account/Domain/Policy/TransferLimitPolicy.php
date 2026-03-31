<?php

namespace App\Account\Domain\Policy;

use App\Account\Domain\Exception\TransferLimitExceededException;
use App\Account\Domain\Port\TransferActivityQuery;
use App\Shared\Domain\ValueObject\Money;

class TransferLimitPolicy implements TransferLimitPolicyInterface
{
    public function __construct(
        private readonly TransferActivityQuery $activityQuery,
        private readonly string $dailyLimit,
    ) {
    }

    public function enforce(string $accountId, Money $amount): void
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $activity = $this->activityQuery->getDailyActivity($accountId, $today);

        $newTotal = bcadd($activity->dailyTotal, $amount->getAmount(), 2);

        if (bccomp($newTotal, $this->dailyLimit, 2) > 0) {
            throw TransferLimitExceededException::dailyLimitExceeded($accountId, $activity->dailyTotal, $amount->getAmount(), $this->dailyLimit);
        }
    }
}
