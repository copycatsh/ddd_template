<?php

namespace App\Command;

use App\User\Domain\Repository\EventSourcedUserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:info',
    description: 'Show user information (from Event Store)',
)]
class GetUserInfoConsoleCommand extends Command
{
    public function __construct(
        private EventSourcedUserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('userId', InputArgument::REQUIRED, 'User ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userId');

        try {
            $user = $this->userRepository->findById($userId);

            if (!$user) {
                $io->error("User not found: $userId");

                return Command::FAILURE;
            }

            $io->title('User Information (from Event Store)');

            $io->table(
                ['Property', 'Value'],
                [
                    ['User ID', $user->getId()],
                    ['Email', $user->getEmail()->getValue()],
                    ['Role', $user->getRole()->value],
                    ['Version', $user->getVersion()],
                ]
            );

            $io->success('User loaded from Event Store successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
