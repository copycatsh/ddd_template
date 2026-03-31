<?php

namespace App\Shared\Domain\ValueObject;

enum Currency: string
{
    case UAH = 'UAH';
    case USD = 'USD';

    public function equals(Currency $other): bool
    {
        return $this->value === $other->value;
    }
}
