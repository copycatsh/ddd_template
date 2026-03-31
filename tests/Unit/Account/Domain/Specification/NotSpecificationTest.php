<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Domain\Specification;

use App\Account\Domain\Specification\AndSpecification;
use App\Account\Domain\Specification\NotSpecification;
use App\Account\Domain\Specification\OrSpecification;
use App\Account\Domain\Specification\SpecificationInterface;
use PHPUnit\Framework\TestCase;

final class NotSpecificationTest extends TestCase
{
    public function testInnerPassesReturnsFalseWithReason(): void
    {
        $spec = new NotSpecification(
            $this->createSpec(true, ''),
        );

        self::assertFalse($spec->isSatisfiedBy('anything'));
        self::assertSame('Condition must not be satisfied', $spec->reason());
    }

    public function testInnerFailsReturnsTrueWithEmptyReason(): void
    {
        $spec = new NotSpecification(
            $this->createSpec(false, 'some reason'),
        );

        self::assertTrue($spec->isSatisfiedBy('anything'));
        self::assertSame('', $spec->reason());
    }

    private function createSpec(bool $satisfied, string $reason): SpecificationInterface
    {
        return new class($satisfied, $reason) implements SpecificationInterface {
            public function __construct(private bool $satisfied, private string $reasonText)
            {
            }

            public function isSatisfiedBy(mixed $candidate): bool
            {
                return $this->satisfied;
            }

            public function reason(): string
            {
                return $this->reasonText;
            }

            public function and(SpecificationInterface $other): SpecificationInterface
            {
                return new AndSpecification($this, $other);
            }

            public function or(SpecificationInterface $other): SpecificationInterface
            {
                return new OrSpecification($this, $other);
            }

            public function not(): SpecificationInterface
            {
                return new NotSpecification($this);
            }
        };
    }
}
