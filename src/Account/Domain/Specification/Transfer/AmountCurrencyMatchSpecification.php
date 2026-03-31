<?php

namespace App\Account\Domain\Specification\Transfer;

use App\Account\Domain\Specification\AbstractSpecification;

/** @extends AbstractSpecification<TransferRequest> */
final class AmountCurrencyMatchSpecification extends AbstractSpecification
{
    private string $failReason = '';

    /** @param TransferRequest $candidate */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate->amount->getCurrency()->equals($candidate->fromCurrency)) {
            $this->failReason = 'Amount currency must match account currency';

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
