<?php

namespace App\User\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\ApiPlatform\StateProcessor\DeleteUserStateProcessor;
use App\User\Infrastructure\ApiPlatform\StateProvider\GetUserStateProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/users/{id}',
            provider: GetUserStateProvider::class
        ),
        new Delete(
            uriTemplate: '/users/{id}',
            processor: DeleteUserStateProcessor::class
        ),
    ]
)]
class UserResource
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $role,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self(
            $user->getId(),
            $user->getEmail()->getValue(),
            $user->getRole()->value,
            $user->getCreatedAt(),
        );
    }
}
