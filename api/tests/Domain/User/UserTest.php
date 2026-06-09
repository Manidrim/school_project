<?php

declare(strict_types=1);

namespace App\Tests\Domain\User;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Entity\User
 */
final class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        // Arrange
        $email = 'test@example.com';
        $roles = ['ROLE_USER'];
        $password = 'hashedpassword';

        // Act
        $user = new User($email, $roles, $password);

        // Assert
        self::assertSame($email, $user->getEmail());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertSame($password, $user->getPassword());
        self::assertNull($user->getId());
    }

    public function testCreateUserWithMultipleRoles(): void
    {
        // Arrange
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];

        // Act
        $user = new User('admin@test.com', $roles, 'password');

        // Assert
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testCreateUserWithoutParameters(): void
    {
        // Act
        $user = new User();

        // Assert
        self::assertNull($user->getEmail());
        self::assertSame(['ROLE_USER'], $user->getRoles()); // Always includes ROLE_USER
        self::assertNull($user->getPassword());
    }

    public function testSetId(): void
    {
        // Arrange
        $user = new User();

        // Act
        $result = $user->setId(123);

        // Assert
        self::assertSame($user, $result); // Fluent interface
        self::assertSame(123, $user->getId());
    }

    public function testSetEmail(): void
    {
        // Arrange
        $user = new User();

        // Act
        $result = $user->setEmail('new@test.com');

        // Assert
        self::assertSame($user, $result); // Fluent interface
        self::assertSame('new@test.com', $user->getEmail());
    }

    public function testSetRoles(): void
    {
        // Arrange
        $user = new User();
        $roles = ['ROLE_ADMIN', 'ROLE_MODERATOR'];

        // Act
        $result = $user->setRoles($roles);

        // Assert
        self::assertSame($user, $result); // Fluent interface
        self::assertSame(['ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_USER'], $user->getRoles());
    }

    public function testSetPassword(): void
    {
        // Arrange
        $user = new User();

        // Act
        $result = $user->setPassword('newhashedpassword');

        // Assert
        self::assertSame($user, $result); // Fluent interface
        self::assertSame('newhashedpassword', $user->getPassword());
    }

    public function testGetRolesAlwaysIncludesRoleUser(): void
    {
        // Arrange
        $user = new User('test@test.com', ['ROLE_ADMIN'], 'password');

        // Act
        $roles = $user->getRoles();

        // Assert
        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_ADMIN', $roles);
    }

    public function testGetRolesRemovesDuplicates(): void
    {
        // Arrange
        $user = new User('test@test.com', ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER'], 'password');

        // Act
        $roles = $user->getRoles();

        // Assert
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], \array_values($roles));
    }

    public function testHasRole(): void
    {
        // Arrange
        $user = new User('test@test.com', ['ROLE_ADMIN'], 'password');

        // Assert
        self::assertTrue($user->hasRole('ROLE_USER')); // Always present
        self::assertTrue($user->hasRole('ROLE_ADMIN'));
        self::assertFalse($user->hasRole('ROLE_MODERATOR'));
    }

    public function testIsAdmin(): void
    {
        // Arrange
        $adminUser = new User('admin@test.com', ['ROLE_ADMIN'], 'password');
        $regularUser = new User('user@test.com', ['ROLE_USER'], 'password');

        // Assert
        self::assertTrue($adminUser->isAdmin());
        self::assertFalse($regularUser->isAdmin());
    }

    public function testIsUser(): void
    {
        // Arrange
        $user = new User('test@test.com', ['ROLE_ADMIN'], 'password');

        // Assert - Every user should have ROLE_USER
        self::assertTrue($user->hasRole('ROLE_USER'));
    }

    public function testEmptyRolesArrayStillIncludesRoleUser(): void
    {
        // Arrange
        $user = new User('test@test.com', [], 'password');

        // Act
        $roles = $user->getRoles();

        // Assert
        self::assertSame(['ROLE_USER'], $roles);
    }

    public function testSetRolesWithEmptyArrayStillIncludesRoleUser(): void
    {
        // Arrange
        $user = new User();

        // Act
        $user->setRoles([]);

        // Assert
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }
}
