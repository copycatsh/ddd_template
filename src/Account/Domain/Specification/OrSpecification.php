<?php

namespace App\Account\Domain\Specification;

/**
 * @template T
 *
 * @extends AbstractSpecification<T>
 */
class OrSpecification extends AbstractSpecification
{
    private string $failReason = '';

    /**
     * @param SpecificationInterface<T> $left
     * @param SpecificationInterface<T> $right
     */
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right,
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if ($this->left->isSatisfiedBy($candidate)) {
            $this->failReason = '';

            return true;
        }

        if ($this->right->isSatisfiedBy($candidate)) {
            $this->failReason = '';

            return true;
        }

        $this->failReason = $this->left->reason().'; '.$this->right->reason();

        return false;
    }

    public function reason(): string
    {
        return $this->failReason;
    }
}
