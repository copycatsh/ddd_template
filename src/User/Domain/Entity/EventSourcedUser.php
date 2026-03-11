<?php

namespace App\User\Domain\Entity;

use App\Shared\Domain\Aggregate\AbstractAggregateRoot;
use App\User\Domain\Event\UserCreatedEvent;
use App\User\Domain\Event\UserEmailChangedEvent;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class EventSourcedUser extends AbstractAggregateRoot implements UserInterface, PasswordAuthenticatedUserInterface
{
    private Email $email;
    private string $password;
    private UserRole $role;

    public static function create(string $userId, string $email, string $hashedPassword, UserRole $role): self
    {
        $user = new self($userId);
        $user->recordEvent(new UserCreatedEvent($userId, $email, $hashedPassword, $role));

        return $user;
    }

    public function changeEmail(Email $newEmail): void
    {
        if ($this->email->equals($newEmail)) {
            throw new \DomainException('New email must be different from current email');
        }

        $this->recordEvent(new UserEmailChangedEvent(
            $this->getId(),
            $this->email,
            $newEmail
        ));
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getRoles(): array
    {
        return [$this->role->value];
    }

    public function getUserIdentifier(): string
    {
        return $this->email->getValue();
    }

    public function eraseCredentials(): void
    {
        // Nothing to clear as we don't store plain passwords
    }

    protected function applyUserCreatedEvent(UserCreatedEvent $event): void
    {
        $this->email = new Email($event->getEmail());
        $this->password = $event->getHashedPassword();
        $this->role = $event->getRole();
    }

    protected function applyUserEmailChangedEvent(UserEmailChangedEvent $event): void
    {
        $this->email = $event->getNewEmail();
    }
}
