<?php

namespace App\Account\Domain\Specification;

/**
 * @template T
 */
interface SpecificationInterface
{
    /** @param T $candidate */
    public function isSatisfiedBy(mixed $candidate): bool;

    public function reason(): string;

    /** @param SpecificationInterface<T> $other
     *  @return SpecificationInterface<T> */
    public function and(self $other): self;

    /** @param SpecificationInterface<T> $other
     *  @return SpecificationInterface<T> */
    public function or(self $other): self;

    /** @return SpecificationInterface<T> */
    public function not(): self;
}
