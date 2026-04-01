<?php

namespace App\User\Application\Handler;

use App\User\Application\Command\CreateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
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
        $existingUser = $this->userRepository->findByEmail($command->getEmail());
        if ($existingUser) {
            throw UserAlreadyExistsException::withEmail($command->getEmail());
        }

        $userId = Uuid::v4()->toRfc4122();
        $email = new Email($command->getEmail());

        $user = new User($userId, $email, '', $command->getRole());

        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->getPassword());

        $user = new User($userId, $email, $hashedPassword, $command->getRole());

        $this->userRepository->save($user);

        return $userId;
    }
}
