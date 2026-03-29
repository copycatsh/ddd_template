<?php

declare(strict_types=1);

namespace App\Account\Domain\Factory;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\ValueObject\Currency;
use Symfony\Component\Uid\Uuid;

class AccountFactory
{
    public function create(string $userId, Currency $currency): Account
    {
        return new Account(
            Uuid::v4()->toRfc4122(),
            $userId,
            $currency,
        );
    }
}
