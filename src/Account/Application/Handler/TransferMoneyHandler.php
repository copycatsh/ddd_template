<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Command\TransferMoneyCommand;
use App\Account\Application\Saga\TransferMoneySaga;

class TransferMoneyHandler
{
    public function __construct(
        private readonly TransferMoneySaga $saga,
    ) {
    }

    public function handle(TransferMoneyCommand $command): string
    {
        return $this->saga->execute(
            $command->getFromAccountId(),
            $command->getToAccountId(),
            $command->getAmount()
        );
    }
}
