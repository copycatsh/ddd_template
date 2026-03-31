<?php

namespace App\Account\Domain\Specification;

/**
 * @template T
 *
 * @implements SpecificationInterface<T>
 */
abstract class AbstractSpecification implements SpecificationInterface
{
    /** @param SpecificationInterface<T> $other
     *  @return SpecificationInterface<T> */
    public function and(SpecificationInterface $other): SpecificationInterface
    {
        return new AndSpecification($this, $other);
    }

    /** @param SpecificationInterface<T> $other
     *  @return SpecificationInterface<T> */
    public function or(SpecificationInterface $other): SpecificationInterface
    {
        return new OrSpecification($this, $other);
    }

    /** @return SpecificationInterface<T> */
    public function not(): SpecificationInterface
    {
        return new NotSpecification($this);
    }
}
