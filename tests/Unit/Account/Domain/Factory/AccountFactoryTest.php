<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Domain\Factory;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\Factory\AccountFactory;
use App\Account\Domain\ValueObject\Currency;
use PHPUnit\Framework\TestCase;

class AccountFactoryTest extends TestCase
{
    private AccountFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new AccountFactory();
    }

    public function testCreateReturnsAccountWithGeneratedId(): void
    {
        $account = $this->factory->create('user-123', Currency::UAH);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertNotEmpty($account->getId());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $account->getId()
        );
    }

    public function testCreateSetsCorrectUserIdAndCurrency(): void
    {
        $account = $this->factory->create('user-456', Currency::USD);

        $this->assertEquals('user-456', $account->getUserId());
        $this->assertEquals(Currency::USD, $account->getCurrency());
        $this->assertEquals('0.00', $account->getBalance()->getAmount());
    }

    public function testCreateGeneratesUniqueIds(): void
    {
        $account1 = $this->factory->create('user-123', Currency::UAH);
        $account2 = $this->factory->create('user-123', Currency::UAH);

        $this->assertNotEquals($account1->getId(), $account2->getId());
    }
}
