<?php

namespace App\Account\Domain\Specification\Transfer;

use App\Account\Domain\Specification\AbstractSpecification;

/** @extends AbstractSpecification<TransferRequest> */
final class NotSameAccountSpecification extends AbstractSpecification
{
    private string $failReason = '';

    /** @param TransferRequest $candidate */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if ($candidate->fromAccountId === $candidate->toAccountId) {
            $this->failReason = 'Cannot transfer to the same account';

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
