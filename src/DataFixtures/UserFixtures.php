<?php

namespace App\DataFixtures;

use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const REGULAR_USER_REFERENCE = 'regular-user';
    public const ANOTHER_USER_REFERENCE = 'another-user';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $adminUser = new User(
            '550e8400-e29b-41d4-a716-446655440000',
            'admin@fintech.com',
            '',
            UserRole::ADMIN
        );

        $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'admin123');
        $adminUser = new User(
            '550e8400-e29b-41d4-a716-446655440000',
            'admin@fintech.com',
            $hashedPassword,
            UserRole::ADMIN
        );

        $manager->persist($adminUser);
        $this->addReference(self::ADMIN_USER_REFERENCE, $adminUser);

        // Create regular user
        $regularUser = new User(
            '550e8400-e29b-41d4-a716-446655440001',
            'user@fintech.com',
            '',
            UserRole::USER
        );

        $hashedPassword = $this->passwordHasher->hashPassword($regularUser, 'user123');
        $regularUser = new User(
            '550e8400-e29b-41d4-a716-446655440001',
            'user@fintech.com',
            $hashedPassword,
            UserRole::USER
        );

        $manager->persist($regularUser);
        $this->addReference(self::REGULAR_USER_REFERENCE, $regularUser);

        // Create another user for testing transfers
        $anotherUser = new User(
            '550e8400-e29b-41d4-a716-446655440002',
            'another@fintech.com',
            '',
            UserRole::USER
        );

        $hashedPassword = $this->passwordHasher->hashPassword($anotherUser, 'another123');
        $anotherUser = new User(
            '550e8400-e29b-41d4-a716-446655440002',
            'another@fintech.com',
            $hashedPassword,
            UserRole::USER
        );

        $manager->persist($anotherUser);
        $this->addReference(self::ANOTHER_USER_REFERENCE, $anotherUser);

        $manager->flush();
    }
}
