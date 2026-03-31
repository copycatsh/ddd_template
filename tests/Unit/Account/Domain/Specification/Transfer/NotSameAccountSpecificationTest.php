<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Domain\Specification\Transfer;

use App\Account\Domain\Specification\Transfer\NotSameAccountSpecification;
use App\Account\Domain\Specification\Transfer\TransferRequest;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class NotSameAccountSpecificationTest extends TestCase
{
    private NotSameAccountSpecification $spec;

    protected function setUp(): void
    {
        $this->spec = new NotSameAccountSpecification();
    }

    public function testDifferentAccountsReturnsTrue(): void
    {
        $request = new TransferRequest('acc-1', 'acc-2', Currency::UAH, Currency::UAH, new Money('100.00', Currency::UAH));

        $this->assertTrue($this->spec->isSatisfiedBy($request));
        $this->assertSame('', $this->spec->reason());
    }

    public function testSameAccountReturnsFalse(): void
    {
        $request = new TransferRequest('acc-1', 'acc-1', Currency::UAH, Currency::UAH, new Money('100.00', Currency::UAH));

        $this->assertFalse($this->spec->isSatisfiedBy($request));
        $this->assertSame('Cannot transfer to the same account', $this->spec->reason());
    }
}
