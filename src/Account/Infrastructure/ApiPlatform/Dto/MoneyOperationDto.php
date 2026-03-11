<?php

namespace App\Account\Infrastructure\ApiPlatform\Dto;

use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use Symfony\Component\Validator\Constraints as Assert;

class MoneyOperationDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public string $amount;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['UAH', 'USD'])]
    public string $currency;

    public function getMoney(): Money
    {
        return new Money($this->amount, Currency::from($this->currency));
    }
}
