<?php

namespace App\User\Application\Handler;

use App\User\Application\Command\DeleteUserCommand;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;

class DeleteUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->findById($command->getUserId());
        if (!$user) {
            throw UserNotFoundException::withId($command->getUserId());
        }

        $this->userRepository->delete($user);
    }
}
