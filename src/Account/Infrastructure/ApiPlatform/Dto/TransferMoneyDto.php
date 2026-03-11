<?php

namespace App\Account\Infrastructure\ApiPlatform\Dto;

use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use Symfony\Component\Validator\Constraints as Assert;

class TransferMoneyDto
{
    #[Assert\NotBlank(message: 'Destination account ID is required')]
    #[Assert\Uuid]
    public string $toAccountId;

    #[Assert\NotBlank(message: 'Amount is required')]
    #[Assert\Positive]
    public string $amount;

    #[Assert\NotBlank(message: 'Currency is required')]
    #[Assert\Choice(choices: ['UAH', 'USD'])]
    public string $currency;

    public function getMoney(): Money
    {
        return new Money($this->amount, Currency::from($this->currency));
    }
}
