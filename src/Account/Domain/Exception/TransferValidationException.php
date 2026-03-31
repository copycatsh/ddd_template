<?php

namespace App\Account\Domain\Exception;

use App\Account\Domain\Specification\Transfer\TransferRequest;
use App\Shared\Domain\Exception\DomainException;

class TransferValidationException extends DomainException
{
    public static function fromSpecification(TransferRequest $request, string $reason): self
    {
        return new self(
            sprintf(
                'Transfer validation failed from %s to %s: %s',
                $request->fromAccountId,
                $request->toAccountId,
                $reason,
            )
        );
    }
}
