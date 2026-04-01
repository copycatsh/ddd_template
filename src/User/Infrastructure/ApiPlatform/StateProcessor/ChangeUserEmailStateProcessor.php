<?php

namespace App\User\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\Command\ChangeUserEmailCommand;
use App\User\Application\Handler\ChangeUserEmailHandler;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Infrastructure\ApiPlatform\Dto\ChangeUserEmailDto;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;

class ChangeUserEmailStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ChangeUserEmailHandler $handler,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        if (!$data instanceof ChangeUserEmailDto) {
            throw new \InvalidArgumentException(sprintf('Expected ChangeUserEmailDto, got %s', get_debug_type($data)));
        }

        $userId = $uriVariables['id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }

        $newEmail = new Email($data->email);
        $command = new ChangeUserEmailCommand($userId, $newEmail);

        $this->handler->handle($command);

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \RuntimeException(sprintf('User %s not found after email change', $userId));
        }

        return UserResource::fromUser($user);
    }
}
