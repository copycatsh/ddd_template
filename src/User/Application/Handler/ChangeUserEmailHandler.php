<?php

namespace App\User\Application\Handler;

use App\User\Application\Command\ChangeUserEmailCommand;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;

class ChangeUserEmailHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(ChangeUserEmailCommand $command): void
    {
        $user = $this->userRepository->findById($command->getUserId());
        if (!$user) {
            throw UserNotFoundException::withId($command->getUserId());
        }

        $existingUser = $this->userRepository->findByEmail($command->getNewEmail()->getValue());
        if ($existingUser) {
            throw UserAlreadyExistsException::withEmail($command->getNewEmail()->getValue());
        }

        $user->changeEmail($command->getNewEmail());
        $this->userRepository->save($user);
    }
}
