<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\User\UserRepositoryInterface;
use App\Entity\User;
use App\Infrastructure\User\SymfonyUserAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Create an admin user',
)]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email')
            ->addArgument('password', InputArgument::REQUIRED, 'Admin password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new SymfonyStyle($input, $output);
        $emailArg = $input->getArgument('email');
        $passwordArg = $input->getArgument('password');

        if (!\is_string($emailArg) || !\is_string($passwordArg)) {
            $console->error('Invalid arguments provided');

            return Command::FAILURE;
        }

        $existingUser = $this->userRepository->findByEmail($emailArg);

        if ($existingUser) {
            $console->error('User with this email already exists!');

            return Command::FAILURE;
        }

        $user = new User($emailArg, ['ROLE_ADMIN']);
        $adapter = new SymfonyUserAdapter($user);
        $hashedPassword = $this->passwordHasher->hashPassword($adapter, $passwordArg);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);

        $console->success('Admin user created successfully!');

        return Command::SUCCESS;
    }
}
