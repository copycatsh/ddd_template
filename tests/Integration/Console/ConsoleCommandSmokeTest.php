<?php

declare(strict_types=1);

namespace App\Tests\Integration\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConsoleCommandSmokeTest extends KernelTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
    }

    /**
     * @dataProvider userCommandsProvider
     */
    public function testUserConsoleCommandsAreRegistered(string $commandName): void
    {
        $this->assertTrue(
            $this->application->has($commandName),
            sprintf('Command "%s" is not registered', $commandName)
        );
    }

    /**
     * @dataProvider accountCommandsProvider
     */
    public function testAccountConsoleCommandsAreRegistered(string $commandName): void
    {
        $this->assertTrue(
            $this->application->has($commandName),
            sprintf('Command "%s" is not registered', $commandName)
        );
    }

    public static function userCommandsProvider(): array
    {
        return [
            ['app:create-user'],
            ['app:user:change-email'],
            ['app:user:info'],
        ];
    }

    public static function accountCommandsProvider(): array
    {
        return [
            ['app:deposit-money'],
            ['app:withdraw-money'],
            ['app:transfer-money'],
            ['app:get-account-balance'],
            ['app:account:transactions'],
            ['app:get-user-accounts'],
            ['app:rebuild-account-projections'],
        ];
    }
}
