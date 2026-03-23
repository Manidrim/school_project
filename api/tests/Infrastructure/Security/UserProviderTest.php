<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Domain\User\UserRepositoryInterface;
use App\Entity\User;
use App\Infrastructure\Security\UserProvider;
use App\Infrastructure\User\SymfonyUserAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 *
 * @covers \App\Infrastructure\Security\UserProvider
 */
final class UserProviderTest extends TestCase
{
    private MockObject&UserRepositoryInterface $userRepository;

    private UserProvider $userProvider;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->userProvider = new UserProvider($this->userRepository);
    }

    public function testLoadUserByIdentifierSuccess(): void
    {
        $user = new User('test@example.com', ['ROLE_ADMIN'], 'password123');

        $this->userRepository
            ->expects(self::once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user)
        ;

        $result = $this->userProvider->loadUserByIdentifier('test@example.com');

        self::assertInstanceOf(SymfonyUserAdapter::class, $result);
        self::assertSame('test@example.com', $result->getUserIdentifier());
    }

    public function testLoadUserByIdentifierThrowsExceptionWhenUserNotFound(): void
    {
        $this->userRepository
            ->expects(self::once())
            ->method('findByEmail')
            ->with('nonexistent@example.com')
            ->willReturn(null)
        ;

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User "nonexistent@example.com" not found.');

        $this->userProvider->loadUserByIdentifier('nonexistent@example.com');
    }

    public function testRefreshUserSuccess(): void
    {
        $user = new User('test@example.com', ['ROLE_ADMIN'], 'password123');
        $adapter = new SymfonyUserAdapter($user);

        $this->userRepository
            ->expects(self::once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user)
        ;

        $result = $this->userProvider->refreshUser($adapter);

        self::assertInstanceOf(SymfonyUserAdapter::class, $result);
        self::assertSame('test@example.com', $result->getUserIdentifier());
    }

    public function testRefreshUserThrowsExceptionWhenUserNotFound(): void
    {
        $user = new User('test@example.com', ['ROLE_ADMIN'], 'password123');
        $adapter = new SymfonyUserAdapter($user);

        $this->userRepository
            ->expects(self::once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn(null)
        ;

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User "test@example.com" not found.');

        $this->userProvider->refreshUser($adapter);
    }

    public function testRefreshUserThrowsExceptionForUnsupportedUser(): void
    {
        $unsupportedUser = $this->createMock(UserInterface::class);
        $unsupportedUser->method('getUserIdentifier')->willReturn('test@example.com');

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessageMatches('/Instance of ".+" is not supported\./');

        $this->userProvider->refreshUser($unsupportedUser);
    }

    public function testSupportsClassReturnsTrueForSymfonyUserAdapter(): void
    {
        self::assertTrue($this->userProvider->supportsClass(SymfonyUserAdapter::class));
    }

    public function testSupportsClassReturnsFalseForOtherClasses(): void
    {
        self::assertFalse($this->userProvider->supportsClass(UserInterface::class));
        self::assertFalse($this->userProvider->supportsClass('SomeOtherClass'));
    }
}
