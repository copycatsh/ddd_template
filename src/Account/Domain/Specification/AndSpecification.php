<?php

namespace App\Account\Domain\Specification;

/**
 * @template T
 *
 * @extends AbstractSpecification<T>
 */
class AndSpecification extends AbstractSpecification
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
        if (!$this->left->isSatisfiedBy($candidate)) {
            $this->failReason = $this->left->reason();

            return false;
        }

        if (!$this->right->isSatisfiedBy($candidate)) {
            $this->failReason = $this->right->reason();

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
