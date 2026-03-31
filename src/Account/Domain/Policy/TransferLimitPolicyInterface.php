<?php

namespace App\Account\Domain\Policy;

use App\Account\Domain\Exception\TransferLimitExceededException;
use App\Shared\Domain\ValueObject\Money;

interface TransferLimitPolicyInterface
{
    /** @throws TransferLimitExceededException */
    public function enforce(string $accountId, Money $amount): void;
}
