<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Domain\Specification;

use App\Account\Domain\Specification\AndSpecification;
use App\Account\Domain\Specification\NotSpecification;
use App\Account\Domain\Specification\OrSpecification;
use App\Account\Domain\Specification\SpecificationInterface;
use PHPUnit\Framework\TestCase;

final class OrSpecificationTest extends TestCase
{
    public function testBothFailReturnsFalseWithCombinedReasons(): void
    {
        $spec = new OrSpecification(
            $this->createSpec(false, 'left failed'),
            $this->createSpec(false, 'right failed'),
        );

        self::assertFalse($spec->isSatisfiedBy('anything'));
        self::assertSame('left failed; right failed', $spec->reason());
    }

    public function testLeftPassesReturnsTrue(): void
    {
        $spec = new OrSpecification(
            $this->createSpec(true, ''),
            $this->createSpec(false, 'right failed'),
        );

        self::assertTrue($spec->isSatisfiedBy('anything'));
        self::assertSame('', $spec->reason());
    }

    public function testRightPassesReturnsTrue(): void
    {
        $spec = new OrSpecification(
            $this->createSpec(false, 'left failed'),
            $this->createSpec(true, ''),
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
