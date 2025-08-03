<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\User;

use App\Entity\User;
use App\Infrastructure\User\SymfonyUserAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Infrastructure\User\SymfonyUserAdapter
 */
final class SymfonyUserAdapterTest extends TestCase
{
    public function testConstructorAndGetUser(): void
    {
        $user = new User('test@example.com', ['ROLE_ADMIN'], 'password123');
        $adapter = new SymfonyUserAdapter($user);

        self::assertSame($user, $adapter->getUser());
    }

    public function testGetRoles(): void
    {
        $user = new User('test@example.com', ['ROLE_ADMIN'], 'password123');
        $adapter = new SymfonyUserAdapter($user);

        $roles = $adapter->getRoles();
        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_USER', $roles);
    }

    public function testEraseCredentialsDoesNothing(): void
    {
        $user = new User('test@example.com', ['ROLE_ADMIN'], 'password123');
        $adapter = new SymfonyUserAdapter($user);

        $adapter->eraseCredentials();

        self::assertNotNull($adapter->getPassword());
    }

    public function testGetUserIdentifier(): void
    {
        $user = new User('test@example.com', ['ROLE_ADMIN'], 'password123');
        $adapter = new SymfonyUserAdapter($user);

        self::assertSame('test@example.com', $adapter->getUserIdentifier());
    }

    public function testGetPassword(): void
    {
        $user = new User('test@example.com', ['ROLE_ADMIN'], 'password123');
        $adapter = new SymfonyUserAdapter($user);

        self::assertSame('password123', $adapter->getPassword());
    }

    public function testGetPasswordWithNullPassword(): void
    {
        $user = new User('test@example.com', ['ROLE_ADMIN']);
        $adapter = new SymfonyUserAdapter($user);

        self::assertNull($adapter->getPassword());
    }
}
