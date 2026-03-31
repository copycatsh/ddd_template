<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Domain\Specification;

use App\Account\Domain\Specification\AndSpecification;
use App\Account\Domain\Specification\NotSpecification;
use App\Account\Domain\Specification\OrSpecification;
use App\Account\Domain\Specification\SpecificationInterface;
use PHPUnit\Framework\TestCase;

final class AndSpecificationTest extends TestCase
{
    public function testBothSatisfiedReturnsTrue(): void
    {
        $spec = new AndSpecification(
            $this->createSpec(true, ''),
            $this->createSpec(true, ''),
        );

        self::assertTrue($spec->isSatisfiedBy('anything'));
        self::assertSame('', $spec->reason());
    }

    public function testLeftFailsReturnsFalseWithLeftReason(): void
    {
        $spec = new AndSpecification(
            $this->createSpec(false, 'left failed'),
            $this->createSpec(true, ''),
        );

        self::assertFalse($spec->isSatisfiedBy('anything'));
        self::assertSame('left failed', $spec->reason());
    }

    public function testRightFailsReturnsFalseWithRightReason(): void
    {
        $spec = new AndSpecification(
            $this->createSpec(true, ''),
            $this->createSpec(false, 'right failed'),
        );

        self::assertFalse($spec->isSatisfiedBy('anything'));
        self::assertSame('right failed', $spec->reason());
    }

    public function testBothFailReturnsFirstReason(): void
    {
        $spec = new AndSpecification(
            $this->createSpec(false, 'first reason'),
            $this->createSpec(false, 'second reason'),
        );

        self::assertFalse($spec->isSatisfiedBy('anything'));
        self::assertSame('first reason', $spec->reason());
    }

    public function testChainedComposition(): void
    {
        $spec = $this->createSpec(true, '')
            ->and($this->createSpec(true, ''))
            ->and($this->createSpec(false, 'third failed'));

        self::assertFalse($spec->isSatisfiedBy('anything'));
        self::assertSame('third failed', $spec->reason());
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
