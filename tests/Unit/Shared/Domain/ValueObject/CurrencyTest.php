<?php

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function testCurrencyUAHCreation(): void
    {
        $currency = Currency::UAH;

        $this->assertEquals('UAH', $currency->value);
    }

    public function testCurrencyUSDCreation(): void
    {
        $currency = Currency::USD;

        $this->assertEquals('USD', $currency->value);
    }

    public function testCurrencyEquality(): void
    {
        $currency1 = Currency::UAH;
        $currency2 = Currency::UAH;

        $this->assertTrue($currency1->equals($currency2));
    }

    public function testCurrencyInequality(): void
    {
        $currency1 = Currency::UAH;
        $currency2 = Currency::USD;

        $this->assertFalse($currency1->equals($currency2));
    }

    public function testCurrencyFromString(): void
    {
        $currency = Currency::from('UAH');

        $this->assertEquals(Currency::UAH, $currency);
    }

    public function testAllCurrenciesAreAvailable(): void
    {
        $currencies = Currency::cases();

        $this->assertCount(2, $currencies);
        $this->assertContains(Currency::UAH, $currencies);
        $this->assertContains(Currency::USD, $currencies);
    }
}
