<?php

namespace App\Account\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Infrastructure\ApiPlatform\Dto\MoneyOperationDto;
use App\Account\Infrastructure\ApiPlatform\Dto\TransferMoneyDto;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\DepositMoneyStateProcessor;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\TransferMoneyStateProcessor;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\WithdrawMoneyStateProcessor;
use App\Account\Infrastructure\ApiPlatform\StateProvider\AccountBalanceStateProvider;
use App\Account\Infrastructure\ApiPlatform\StateProvider\AccountTransactionsStateProvider;
use App\Account\Infrastructure\ApiPlatform\StateProvider\UserAccountsStateProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/accounts/{id}',
            provider: AccountBalanceStateProvider::class
        ),
        new GetCollection(
            uriTemplate: '/users/{userId}/accounts',
            provider: UserAccountsStateProvider::class
        ),
        new Put(
            uriTemplate: '/accounts/{id}/deposit',
            input: MoneyOperationDto::class,
            processor: DepositMoneyStateProcessor::class
        ),
        new Put(
            uriTemplate: '/accounts/{id}/withdraw',
            input: MoneyOperationDto::class,
            processor: WithdrawMoneyStateProcessor::class
        ),
        new Put(
            uriTemplate: '/accounts/{id}/transfer',
            input: TransferMoneyDto::class,
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

    public static function fromEventSourcedAccount(EventSourcedAccount $account): self
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
