<?php

namespace App\Command;

use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Handler\EventSourcedCreateUserHandler;
use App\User\Domain\ValueObject\UserRole;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:create-es',
    description: 'Create user via Event Sourcing',
)]
class CreateUserEventSourcedConsoleCommand extends Command
{
    public function __construct(
        private EventSourcedCreateUserHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
            ->addOption('role', 'r', InputOption::VALUE_OPTIONAL, 'User role', 'USER')
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
            $command = new CreateUserCommand($email, $password, $role);

            $userId = $this->handler->handle($command);

            $io->success([
                'User created via Event Sourcing!',
                "User ID: $userId",
                "Email: $email",
            ]);

            $io->note('Check Event Store: SELECT * FROM event_store WHERE aggregate_id = \''.$userId.'\'');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
