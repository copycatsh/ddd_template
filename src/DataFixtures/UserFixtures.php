<?php

namespace App\DataFixtures;

use App\User\Domain\Entity\User;
use App\User\Domain\Port\PasswordHasherInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const REGULAR_USER_REFERENCE = 'regular-user';
    public const ANOTHER_USER_REFERENCE = 'another-user';

    public function __construct(
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $adminUser = User::create(
            '550e8400-e29b-41d4-a716-446655440000',
            new Email('admin@fintech.com'),
            $this->passwordHasher->hash('admin123'),
            UserRole::ADMIN
        );
        $manager->persist($adminUser);
        $this->addReference(self::ADMIN_USER_REFERENCE, $adminUser);

        $regularUser = User::create(
            '550e8400-e29b-41d4-a716-446655440001',
            new Email('user@fintech.com'),
            $this->passwordHasher->hash('user123'),
            UserRole::USER
        );
        $manager->persist($regularUser);
        $this->addReference(self::REGULAR_USER_REFERENCE, $regularUser);

        $anotherUser = User::create(
            '550e8400-e29b-41d4-a716-446655440002',
            new Email('another@fintech.com'),
            $this->passwordHasher->hash('another123'),
            UserRole::USER
        );
        $manager->persist($anotherUser);
        $this->addReference(self::ANOTHER_USER_REFERENCE, $anotherUser);

        $manager->flush();
    }
}
