<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Command\WithdrawMoneyCommand;
use App\Account\Application\Handler\WithdrawMoneyHandler;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Infrastructure\ApiPlatform\Dto\MoneyOperationDto;

class WithdrawMoneyStateProcessor implements ProcessorInterface
{
    public function __construct(
        private WithdrawMoneyHandler $handler,
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof MoneyOperationDto) {
            throw new \InvalidArgumentException('Expected MoneyOperationDto');
        }

        $accountId = $uriVariables['id'] ?? null;
        if (!$accountId) {
            throw new \InvalidArgumentException('Account ID is required');
        }

        $command = new WithdrawMoneyCommand(
            $accountId,
            $data->getMoney()
        );

        $this->handler->handle($command);

        return $this->accountRepository->findById($accountId);
    }
}
