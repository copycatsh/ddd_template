<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Query\GetAccountTransactionsQuery;
use App\Account\Application\Query\Response\TransactionDto;
use App\Transaction\Domain\Repository\TransactionRepositoryInterface;

class GetAccountTransactionsHandler
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {
    }

    /**
     * @return TransactionDto[]
     */
    public function handle(GetAccountTransactionsQuery $query): array
    {
        $transactions = $this->transactionRepository->findByAccountId($query->getAccountId());

        return array_map(
            fn ($transaction) => TransactionDto::fromEntity($transaction),
            $transactions
        );
    }
}
