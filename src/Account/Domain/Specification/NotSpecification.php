<?php

namespace App\Account\Domain\Specification;

/**
 * @template T
 *
 * @extends AbstractSpecification<T>
 */
class NotSpecification extends AbstractSpecification
{
    private string $failReason = '';

    /** @param SpecificationInterface<T> $inner */
    public function __construct(
        private readonly SpecificationInterface $inner,
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if ($this->inner->isSatisfiedBy($candidate)) {
            $this->failReason = 'Condition must not be satisfied';

            return false;
        }

        $this->failReason = '';

        return true;
    }

    public function reason(): string
    {
        return $this->failReason;
    }
}
