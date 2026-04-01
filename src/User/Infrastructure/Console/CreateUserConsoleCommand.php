<?php

namespace App\User\Infrastructure\Console;

use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Handler\CreateUserHandler;
use App\User\Domain\ValueObject\UserRole;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user via CQRS command',
)]
class CreateUserConsoleCommand extends Command
{
    public function __construct(
        private CreateUserHandler $createUserHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
            ->addOption('role', 'r', InputOption::VALUE_OPTIONAL, 'User role (USER or ADMIN)', 'USER')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $roleString = $input->getOption('role');

        try {
            $role = UserRole::from('ROLE_'.strtoupper($roleString));
        } catch (\ValueError $e) {
            $io->error('Invalid role. Use USER or ADMIN.');

            return Command::FAILURE;
        }

        $command = new CreateUserCommand($email, $password, $role);

        try {
            $userId = $this->createUserHandler->handle($command);
            $io->success("User created successfully with ID: $userId");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating user: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
