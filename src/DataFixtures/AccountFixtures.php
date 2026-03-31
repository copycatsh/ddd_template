<?php

namespace App\DataFixtures;

use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Application\Command\DepositMoneyCommand;
use App\Account\Application\Handler\CreateAccountHandler;
use App\Account\Application\Handler\DepositMoneyHandler;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\User\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AccountFixtures extends Fixture implements DependentFixtureInterface
{
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

        $this->createAndDeposit($adminUser->getId(), Currency::UAH, '50000.00');
        $this->createAndDeposit($adminUser->getId(), Currency::USD, '1000.00');
        $this->createAndDeposit($regularUser->getId(), Currency::UAH, '10000.00');
        $this->createAndDeposit($regularUser->getId(), Currency::USD, '250.00');
        $this->createAndDeposit($anotherUser->getId(), Currency::UAH, '5000.00');
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
