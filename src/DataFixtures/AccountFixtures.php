<?php

namespace App\DataFixtures;

use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Application\Command\DepositMoneyCommand;
use App\Account\Application\Handler\CreateAccountHandler;
use App\Account\Application\Handler\DepositMoneyHandler;
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

    public function __construct(
        private readonly CreateAccountHandler $createAccountHandler,
        private readonly DepositMoneyHandler $depositMoneyHandler,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        /** @var User $adminUser */
        $adminUser = $this->getReference(UserFixtures::ADMIN_USER_REFERENCE);

        /** @var User $regularUser */
        $regularUser = $this->getReference(UserFixtures::REGULAR_USER_REFERENCE);

        /** @var User $anotherUser */
        $anotherUser = $this->getReference(UserFixtures::ANOTHER_USER_REFERENCE);

        $adminUahId = $this->createAndDeposit($adminUser->getId(), Currency::UAH, '50000.00');
        $this->addReference(self::ADMIN_UAH_ACCOUNT_REFERENCE, (object) ['id' => $adminUahId]);

        $adminUsdId = $this->createAndDeposit($adminUser->getId(), Currency::USD, '1000.00');
        $this->addReference(self::ADMIN_USD_ACCOUNT_REFERENCE, (object) ['id' => $adminUsdId]);

        $userUahId = $this->createAndDeposit($regularUser->getId(), Currency::UAH, '10000.00');
        $this->addReference(self::USER_UAH_ACCOUNT_REFERENCE, (object) ['id' => $userUahId]);

        $userUsdId = $this->createAndDeposit($regularUser->getId(), Currency::USD, '250.00');
        $this->addReference(self::USER_USD_ACCOUNT_REFERENCE, (object) ['id' => $userUsdId]);

        $anotherUahId = $this->createAndDeposit($anotherUser->getId(), Currency::UAH, '5000.00');
        $this->addReference(self::ANOTHER_UAH_ACCOUNT_REFERENCE, (object) ['id' => $anotherUahId]);
    }

    private function createAndDeposit(string $userId, Currency $currency, string $amount): string
    {
        $accountId = $this->createAccountHandler->handle(new CreateAccountCommand($userId, $currency));
        $this->depositMoneyHandler->handle(new DepositMoneyCommand($accountId, new Money($amount, $currency)));

        return $accountId;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
