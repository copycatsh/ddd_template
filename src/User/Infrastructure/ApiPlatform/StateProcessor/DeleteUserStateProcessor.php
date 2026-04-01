<?php

namespace App\User\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Handler\GetUserAccountsHandler;
use App\Account\Application\Query\GetUserAccountsQuery;
use App\User\Application\Command\DeleteUserCommand;
use App\User\Application\Handler\DeleteUserHandler;
use App\User\Domain\Exception\UserHasActiveAccountsException;

class DeleteUserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly DeleteUserHandler $handler,
        private readonly GetUserAccountsHandler $accountsHandler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $userId = $uriVariables['id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }

        $accountsResponse = $this->accountsHandler->handle(new GetUserAccountsQuery($userId));
        if (!empty($accountsResponse->accounts)) {
            throw UserHasActiveAccountsException::withId($userId);
        }

        $command = new DeleteUserCommand($userId);
        $this->handler->handle($command);

        return null;
    }
}
