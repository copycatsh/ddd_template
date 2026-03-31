<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Domain\Specification\Transfer;

use App\Account\Domain\Specification\Transfer\CurrencyMatchSpecification;
use App\Account\Domain\Specification\Transfer\TransferRequest;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class CurrencyMatchSpecificationTest extends TestCase
{
    private CurrencyMatchSpecification $spec;

    protected function setUp(): void
    {
        $this->spec = new CurrencyMatchSpecification();
    }

    public function testMatchingCurrenciesReturnsTrue(): void
    {
        $request = new TransferRequest('acc-1', 'acc-2', Currency::UAH, Currency::UAH, new Money('100.00', Currency::UAH));

        $this->assertTrue($this->spec->isSatisfiedBy($request));
        $this->assertSame('', $this->spec->reason());
    }

    public function testDifferentCurrenciesReturnsFalse(): void
    {
        $request = new TransferRequest('acc-1', 'acc-2', Currency::UAH, Currency::USD, new Money('100.00', Currency::UAH));

        $this->assertFalse($this->spec->isSatisfiedBy($request));
        $this->assertSame('Cannot transfer between different currencies', $this->spec->reason());
    }
}
