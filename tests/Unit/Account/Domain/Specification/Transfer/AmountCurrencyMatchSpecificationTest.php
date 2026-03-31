<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Domain\Specification\Transfer;

use App\Account\Domain\Specification\Transfer\AmountCurrencyMatchSpecification;
use App\Account\Domain\Specification\Transfer\TransferRequest;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class AmountCurrencyMatchSpecificationTest extends TestCase
{
    private AmountCurrencyMatchSpecification $spec;

    protected function setUp(): void
    {
        $this->spec = new AmountCurrencyMatchSpecification();
    }

    public function testMatchingAmountCurrencyReturnsTrue(): void
    {
        $request = new TransferRequest('acc-1', 'acc-2', Currency::UAH, Currency::UAH, new Money('100.00', Currency::UAH));

        $this->assertTrue($this->spec->isSatisfiedBy($request));
        $this->assertSame('', $this->spec->reason());
    }

    public function testMismatchedAmountCurrencyReturnsFalse(): void
    {
        $request = new TransferRequest('acc-1', 'acc-2', Currency::UAH, Currency::UAH, new Money('100.00', Currency::USD));

        $this->assertFalse($this->spec->isSatisfiedBy($request));
        $this->assertSame('Amount currency must match account currency', $this->spec->reason());
    }
}
