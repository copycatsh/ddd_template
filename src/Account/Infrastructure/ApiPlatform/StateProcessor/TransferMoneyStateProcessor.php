<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Command\TransferMoneyCommand;
use App\Account\Application\Handler\TransferMoneyHandler;
use App\Account\Infrastructure\ApiPlatform\Dto\TransferMoneyDto;

class TransferMoneyStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly TransferMoneyHandler $handler,
    ) {
    }

    /**
     * Process transfer money request.
     *
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

        $command = new TransferMoneyCommand(
            $fromAccountId,
            $data->toAccountId,
            $data->getMoney()
        );

        // Execute via Handler → Saga
        $transactionId = $this->handler->handle($command);

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
