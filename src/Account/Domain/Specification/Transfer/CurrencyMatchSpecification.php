<?php

namespace App\Account\Domain\Specification\Transfer;

use App\Account\Domain\Specification\AbstractSpecification;

/** @extends AbstractSpecification<TransferRequest> */
final class CurrencyMatchSpecification extends AbstractSpecification
{
    private string $failReason = '';

    /** @param TransferRequest $candidate */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate->fromCurrency->equals($candidate->toCurrency)) {
            $this->failReason = 'Cannot transfer between different currencies';

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
