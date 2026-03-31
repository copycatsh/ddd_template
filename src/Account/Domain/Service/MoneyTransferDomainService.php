<?php

namespace App\Account\Domain\Service;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\Exception\TransferValidationException;
use App\Account\Domain\Policy\TransferLimitPolicyInterface;
use App\Account\Domain\Specification\SpecificationInterface;
use App\Account\Domain\Specification\Transfer\TransferRequest;
use App\Shared\Domain\ValueObject\Money;

class MoneyTransferDomainService
{
    /**
     * @param SpecificationInterface<TransferRequest> $transferSpecification
     */
    public function __construct(
        private readonly SpecificationInterface $transferSpecification,
        private readonly TransferLimitPolicyInterface $transferLimitPolicy,
    ) {
    }

    public function validate(Account $from, Account $to, Money $amount): void
    {
        $request = new TransferRequest(
            $from->getId(),
            $to->getId(),
            $from->getCurrency(),
            $to->getCurrency(),
            $amount,
        );

        if (!$this->transferSpecification->isSatisfiedBy($request)) {
            throw TransferValidationException::fromSpecification($request, $this->transferSpecification->reason());
        }

        $this->transferLimitPolicy->enforce($from->getId(), $amount);
    }
}
