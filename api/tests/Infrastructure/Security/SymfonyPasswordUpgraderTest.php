<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Domain\User\UserRepositoryInterface;
use App\Entity\User;
use App\Infrastructure\Security\SymfonyPasswordUpgrader;
use App\Infrastructure\User\SymfonyUserAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @internal
 *
 * @covers \App\Infrastructure\Security\SymfonyPasswordUpgrader
 */
final class SymfonyPasswordUpgraderTest extends TestCase
{
    private MockObject&UserRepositoryInterface $userRepository;

    private SymfonyPasswordUpgrader $passwordUpgrader;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordUpgrader = new SymfonyPasswordUpgrader($this->userRepository);
    }

    public function testUpgradePasswordSuccess(): void
    {
        $user = new User('test@example.com', ['ROLE_USER'], 'oldpassword');
        $adapter = new SymfonyUserAdapter($user);
        $newHashedPassword = 'new-hashed-password';

        $this->userRepository
            ->expects(self::once())
            ->method('save')
            ->with($user)
        ;

        $this->passwordUpgrader->upgradePassword($adapter, $newHashedPassword);

        self::assertSame($newHashedPassword, $user->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        $unsupportedUser = $this->createMock(PasswordAuthenticatedUserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessageMatches('/Instances of ".+" are not supported\./');

        $this->passwordUpgrader->upgradePassword($unsupportedUser, 'new-password');
    }
}
