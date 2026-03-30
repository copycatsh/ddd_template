<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Saga\TransferMoneySaga;
use App\Account\Infrastructure\ApiPlatform\Dto\TransferMoneyDto;

class TransferMoneyStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly TransferMoneySaga $saga,
    ) {
    }

    /**
     * @param TransferMoneyDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof TransferMoneyDto) {
            throw new \InvalidArgumentException('Expected TransferMoneyDto');
        }

        $fromAccountId = $uriVariables['id'] ?? null;
        if (!$fromAccountId) {
            throw new \InvalidArgumentException('Account ID is required');
        }

        $transactionId = $this->saga->execute(
            $fromAccountId,
            $data->toAccountId,
            $data->getMoney()
        );

        return [
            'success' => true,
            'transactionId' => $transactionId,
            'fromAccountId' => $fromAccountId,
            'toAccountId' => $data->toAccountId,
            'amount' => $data->amount,
            'currency' => $data->currency,
        ];
    }
}
