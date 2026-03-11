<?php

namespace App\User\Application\Handler;

use App\User\Application\Command\CreateUserCommand;
use App\User\Domain\Entity\EventSourcedUser;
use App\User\Domain\Repository\EventSourcedUserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class EventSourcedCreateUserHandler
{
    public function __construct(
        private EventSourcedUserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(CreateUserCommand $command): string
    {
        // Check if user already exists
        $existingUser = $this->userRepository->findByEmail($command->getEmail());

        if ($existingUser) {
            throw new \DomainException('User with this email already exists');
        }

        $userId = Uuid::v4()->toRfc4122();

        // Create temporary user instance for password hashing
        $tempUser = EventSourcedUser::create($userId, $command->getEmail(), '', $command->getRole());
        $hashedPassword = $this->passwordHasher->hashPassword($tempUser, $command->getPassword());

        $user = EventSourcedUser::create(
            $userId,
            $command->getEmail(),
            $hashedPassword,
            $command->getRole()
        );

        $this->userRepository->save($user);

        return $userId;
    }
}
