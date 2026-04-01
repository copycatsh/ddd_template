<?php

namespace App\User\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Handler\CreateUserHandler;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserRole;
use App\User\Infrastructure\ApiPlatform\Dto\CreateUserDto;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;

class CreateUserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CreateUserHandler $handler,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        if (!$data instanceof CreateUserDto) {
            throw new \InvalidArgumentException(sprintf('Expected CreateUserDto, got %s', get_debug_type($data)));
        }

        $role = UserRole::from('ROLE_'.strtoupper($data->role));
        $command = new CreateUserCommand($data->email, $data->password, $role);

        $userId = $this->handler->handle($command);

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \RuntimeException(sprintf('User %s not found after creation', $userId));
        }

        return UserResource::fromUser($user);
    }
}
