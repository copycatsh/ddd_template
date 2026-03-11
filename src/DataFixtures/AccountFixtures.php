<?php

namespace App\DataFixtures;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use App\User\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AccountFixtures extends Fixture implements DependentFixtureInterface
{
    public const ADMIN_UAH_ACCOUNT_REFERENCE = 'admin-uah-account';
    public const ADMIN_USD_ACCOUNT_REFERENCE = 'admin-usd-account';
    public const USER_UAH_ACCOUNT_REFERENCE = 'user-uah-account';
    public const USER_USD_ACCOUNT_REFERENCE = 'user-usd-account';
    public const ANOTHER_UAH_ACCOUNT_REFERENCE = 'another-uah-account';

    public function load(ObjectManager $manager): void
    {
        /** @var User $adminUser */
        $adminUser = $this->getReference(UserFixtures::ADMIN_USER_REFERENCE);

        /** @var User $regularUser */
        $regularUser = $this->getReference(UserFixtures::REGULAR_USER_REFERENCE);

        /** @var User $anotherUser */
        $anotherUser = $this->getReference(UserFixtures::ANOTHER_USER_REFERENCE);

        // Create admin accounts
        $adminUahAccount = new Account(
            'account-550e8400-e29b-41d4-a716-446655440000',
            $adminUser->getId(),
            Currency::UAH
        );
        $adminUahAccount->deposit(new Money('50000.00', Currency::UAH));
        $manager->persist($adminUahAccount);
        $this->addReference(self::ADMIN_UAH_ACCOUNT_REFERENCE, $adminUahAccount);

        $adminUsdAccount = new Account(
            'account-550e8400-e29b-41d4-a716-446655440001',
            $adminUser->getId(),
            Currency::USD
        );
        $adminUsdAccount->deposit(new Money('1000.00', Currency::USD));
        $manager->persist($adminUsdAccount);
        $this->addReference(self::ADMIN_USD_ACCOUNT_REFERENCE, $adminUsdAccount);

        // Create regular user accounts
        $userUahAccount = new Account(
            'account-550e8400-e29b-41d4-a716-446655440002',
            $regularUser->getId(),
            Currency::UAH
        );
        $userUahAccount->deposit(new Money('10000.00', Currency::UAH));
        $manager->persist($userUahAccount);
        $this->addReference(self::USER_UAH_ACCOUNT_REFERENCE, $userUahAccount);

        $userUsdAccount = new Account(
            'account-550e8400-e29b-41d4-a716-446655440003',
            $regularUser->getId(),
            Currency::USD
        );
        $userUsdAccount->deposit(new Money('250.00', Currency::USD));
        $manager->persist($userUsdAccount);
        $this->addReference(self::USER_USD_ACCOUNT_REFERENCE, $userUsdAccount);

        // Create another user account
        $anotherUahAccount = new Account(
            'account-550e8400-e29b-41d4-a716-446655440004',
            $anotherUser->getId(),
            Currency::UAH
        );
        $anotherUahAccount->deposit(new Money('5000.00', Currency::UAH));
        $manager->persist($anotherUahAccount);
        $this->addReference(self::ANOTHER_UAH_ACCOUNT_REFERENCE, $anotherUahAccount);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
