<?php

namespace App\User\Infrastructure\ApiPlatform\StateProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;

class GetUserStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        $userId = $uriVariables['id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw UserNotFoundException::withId($userId);
        }

        return UserResource::fromUser($user);
    }
}
