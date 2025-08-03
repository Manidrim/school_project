<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CreateAdminUserCommand;
use App\Domain\User\UserRepositoryInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * @covers \App\Command\CreateAdminUserCommand
 */
final class CreateAdminUserCommandTest extends KernelTestCase
{
    private UserRepositoryInterface $userRepository;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);

        $application = new Application(self::$kernel);
        $command = $application->find('app:create-admin-user');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $emails = ['new-admin@test.com', 'existing@test.com'];

        foreach ($emails as $email) {
            $user = $this->userRepository->findByEmail($email);

            if ($user) {
                $this->userRepository->remove($user);
            }
        }

        parent::tearDown();
    }

    public function testCreateAdminUserSuccess(): void
    {
        $existingUser = $this->userRepository->findByEmail('new-admin@test.com');

        if ($existingUser) {
            $this->userRepository->remove($existingUser);
        }

        $this->commandTester->execute([
            'email' => 'new-admin@test.com',
            'password' => 'secure-password',
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Admin user created successfully!', $this->commandTester->getDisplay());

        $user = $this->userRepository->findByEmail('new-admin@test.com');
        self::assertNotNull($user);
        self::assertSame('new-admin@test.com', $user->getEmail());
        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testCreateAdminUserWithExistingEmail(): void
    {
        $existingUser = $this->userRepository->findByEmail('existing@test.com');

        if (!$existingUser) {
            $existingUser = new User('existing@test.com', ['ROLE_USER'], 'hashed-password');
            $this->userRepository->save($existingUser);
        }

        $this->commandTester->execute([
            'email' => 'existing@test.com',
            'password' => 'secure-password',
        ]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('User with this email already exists!', $this->commandTester->getDisplay());
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getContainer()->get(CreateAdminUserCommand::class);
        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('email'));
        self::assertTrue($definition->hasArgument('password'));

        $emailArg = $definition->getArgument('email');
        $passwordArg = $definition->getArgument('password');

        self::assertTrue($emailArg->isRequired());
        self::assertTrue($passwordArg->isRequired());
        self::assertSame('Admin email', $emailArg->getDescription());
        self::assertSame('Admin password', $passwordArg->getDescription());
    }

    public function testConstructor(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $command = new CreateAdminUserCommand($userRepository, $passwordHasher);

        self::assertInstanceOf(CreateAdminUserCommand::class, $command);
        self::assertSame('app:create-admin-user', $command->getName());
        self::assertSame('Create an admin user', $command->getDescription());
    }

    public function testExecuteWithNullArguments(): void
    {
        // Utiliser reflection pour forcer des arguments null/invalides
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->method('getArgument')
            ->willReturnMap([
                ['email', null],
                ['password', 'valid-password'],
            ])
        ;

        $command = new CreateAdminUserCommand(
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        );

        $result = $command->run($input, $output);

        self::assertSame(Command::FAILURE, $result);
    }
}
