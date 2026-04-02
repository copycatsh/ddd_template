<?php

namespace App\Account\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use App\Account\Domain\Entity\Account;
use App\Account\Infrastructure\ApiPlatform\Dto\MoneyOperationDto;
use App\Account\Infrastructure\ApiPlatform\Dto\TransferMoneyDto;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\DepositMoneyStateProcessor;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\TransferMoneyStateProcessor;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\WithdrawMoneyStateProcessor;
use App\Account\Infrastructure\ApiPlatform\StateProvider\AccountBalanceStateProvider;
use App\Account\Infrastructure\ApiPlatform\StateProvider\AccountTransactionsStateProvider;
use App\Account\Infrastructure\ApiPlatform\StateProvider\UserAccountsStateProvider;
use App\Shared\Domain\ValueObject\Currency;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/accounts/{id}',
            provider: AccountBalanceStateProvider::class
        ),
        new GetCollection(
            uriTemplate: '/users/{userId}/accounts',
            uriVariables: [
                'userId' => new Link(
                    parameterName: 'userId',
                    identifiers: [],
                ),
            ],
            provider: UserAccountsStateProvider::class
        ),
        new Put(
            uriTemplate: '/accounts/{id}/deposit',
            input: MoneyOperationDto::class,
            read: false,
            processor: DepositMoneyStateProcessor::class
        ),
        new Put(
            uriTemplate: '/accounts/{id}/withdraw',
            input: MoneyOperationDto::class,
            read: false,
            processor: WithdrawMoneyStateProcessor::class
        ),
        new Put(
            uriTemplate: '/accounts/{id}/transfer',
            input: TransferMoneyDto::class,
            read: false,
            processor: TransferMoneyStateProcessor::class
        ),
        new Get(
            uriTemplate: '/accounts/{id}/transactions',
            provider: AccountTransactionsStateProvider::class
        ),
    ]
)]
class AccountResource
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly Currency $currency,
        public readonly string $balance,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromAccount(Account $account): self
    {
        return new self(
            $account->getId(),
            $account->getUserId(),
            $account->getCurrency(),
            $account->getBalance()->getAmount(),
            $account->getCreatedAt(),
            $account->getUpdatedAt(),
        );
    }
}
