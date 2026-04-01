<?php

namespace App\User\Domain\Entity;

use App\User\Domain\Exception\SameEmailException;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(type: 'string', length: 20, enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $id, Email $email, string $password, UserRole $role = UserRole::USER)
    {
        $this->id = $id;
        $this->email = $email->getValue();
        $this->password = $password;
        $this->role = $role;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeEmail(Email $newEmail): void
    {
        if ($this->getEmail()->equals($newEmail)) {
            throw SameEmailException::create();
        }

        $this->email = $newEmail->getValue();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return new Email($this->email);
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
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
