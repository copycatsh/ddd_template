<?php

namespace App\User\Application\Handler;

use App\User\Application\Command\CreateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(CreateUserCommand $command): string
    {
        // Check if user already exists
        $existingUser = $this->userRepository->findByEmail($command->getEmail());
        if ($existingUser) {
            throw new \InvalidArgumentException('User with this email already exists');
        }

        $userId = Uuid::v4()->toRfc4122();

        // Create user with temporary password
        $user = new User(
            $userId,
            $command->getEmail(),
            '',
            $command->getRole()
        );

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->getPassword());

        // Create user with hashed password
        $user = new User(
            $userId,
            $command->getEmail(),
            $hashedPassword,
            $command->getRole()
        );

        $this->userRepository->save($user);

        return $userId;
    }
}
