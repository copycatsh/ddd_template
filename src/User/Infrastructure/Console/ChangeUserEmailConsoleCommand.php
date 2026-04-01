<?php

namespace App\User\Infrastructure\Console;

use App\User\Application\Command\ChangeUserEmailCommand;
use App\User\Application\Handler\ChangeUserEmailHandler;
use App\User\Domain\ValueObject\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:change-email',
    description: 'Change user email',
)]
class ChangeUserEmailConsoleCommand extends Command
{
    public function __construct(
        private ChangeUserEmailHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'User ID')
            ->addArgument('newEmail', InputArgument::REQUIRED, 'New email address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getArgument('userId');
        $newEmailString = $input->getArgument('newEmail');

        try {
            $newEmail = new Email($newEmailString);
            $command = new ChangeUserEmailCommand($userId, $newEmail);
            $this->handler->handle($command);

            $io->success([
                'Email changed successfully!',
                "User ID: $userId",
                "New Email: {$newEmail->getValue()}",
            ]);

            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $io->error('Invalid email format: '.$e->getMessage());

            return Command::FAILURE;
        } catch (\DomainException $e) {
            $io->error('Domain error: '.$e->getMessage());

            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error('Error changing email: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
