<?php

namespace App\User\Application\Handler;

use App\User\Application\Command\ChangeUserEmailCommand;
use App\User\Domain\Repository\EventSourcedUserRepositoryInterface;

class EventSourcedChangeUserEmailHandler
{
    public function __construct(
        private EventSourcedUserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(ChangeUserEmailCommand $command): void
    {
        $user = $this->userRepository->findById($command->getUserId());

        if (!$user) {
            throw new \DomainException('User not found');
        }

        $user->changeEmail($command->getNewEmail());

        $this->userRepository->save($user);
    }
}
