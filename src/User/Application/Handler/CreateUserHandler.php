<?php

namespace App\User\Application\Handler;

use App\User\Application\Command\CreateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Port\PasswordHasherInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use Symfony\Component\Uid\Uuid;

class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
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
        $hashedPassword = $this->passwordHasher->hash($command->getPassword());

        $user = User::create($userId, $email, $hashedPassword, $command->getRole());

        $this->userRepository->save($user);

        return $userId;
    }
}
