<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Entity\User
 */
final class UserTest extends TestCase
{
    public function testNewUserHasNullId(): void
    {
        $user = new User();
        self::assertNull($user->getId());
    }

    public function testConstructorSetsPropertiesCorrectly(): void
    {
        $email = 'test@example.com';
        $roles = ['ROLE_ADMIN'];
        $password = 'password123';

        $user = new User($email, $roles, $password);

        self::assertSame($email, $user->getEmail());
        self::assertContains('ROLE_ADMIN', $user->getRoles());
        self::assertSame($password, $user->getPassword());
    }

    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';

        $result = $user->setEmail($email);

        self::assertSame($user, $result);
        self::assertSame($email, $user->getEmail());
        self::assertSame($email, $user->getUserIdentifier());
    }

    public function testSetAndGetRoles(): void
    {
        $user = new User();
        $roles = ['ROLE_ADMIN', 'ROLE_USER'];

        $result = $user->setRoles($roles);

        self::assertSame($user, $result);
        $actualRoles = $user->getRoles();
        self::assertContains('ROLE_USER', $actualRoles);
        self::assertContains('ROLE_ADMIN', $actualRoles);
    }

    public function testGetRolesAlwaysContainsRoleUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();

        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_ADMIN', $roles);
    }

    public function testGetRolesReturnsUniqueRoles(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']);

        $roles = $user->getRoles();

        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], \array_values(\array_unique($roles)));
    }

    public function testSetAndGetPassword(): void
    {
        $user = new User();
        $password = 'hashed-password';

        $result = $user->setPassword($password);

        self::assertSame($user, $result);
        self::assertSame($password, $user->getPassword());
    }

    public function testIsAdminMethod(): void
    {
        $user = new User();
        self::assertFalse($user->isAdmin());

        $user->setRoles(['ROLE_ADMIN']);
        self::assertTrue($user->isAdmin());
    }

    public function testHasRoleMethod(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_EDITOR']);

        self::assertTrue($user->hasRole('ROLE_ADMIN'));
        self::assertTrue($user->hasRole('ROLE_USER')); // Always present
        self::assertFalse($user->hasRole('ROLE_SUPER_ADMIN'));
    }

    public function testUserIdentifierIsEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';
        $user->setEmail($email);

        self::assertSame($email, $user->getUserIdentifier());
    }

    public function testUserIdentifierWorksWithNullEmail(): void
    {
        $user = new User();

        self::assertSame('', $user->getUserIdentifier());
    }

    public function testIsAdminReturnsTrueForAdminUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        self::assertTrue($user->isAdmin());
    }

    public function testIsAdminReturnsFalseForNonAdminUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_EDITOR']);

        self::assertFalse($user->isAdmin());
    }

    public function testSetId(): void
    {
        $user = new User();
        $result = $user->setId(123);

        self::assertSame(123, $user->getId());
        self::assertSame($user, $result);
    }

    public function testSetIdWithNull(): void
    {
        $user = new User();
        $user->setId(456);
        $result = $user->setId(null);

        self::assertNull($user->getId());
        self::assertSame($user, $result);
    }
}
