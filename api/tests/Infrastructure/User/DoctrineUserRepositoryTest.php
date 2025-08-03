<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\User;

use App\Entity\User;
use App\Infrastructure\User\DoctrineUserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Infrastructure\User\DoctrineUserRepository
 */
final class DoctrineUserRepositoryTest extends KernelTestCase
{
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->repository = $container->get(DoctrineUserRepository::class);
    }

    public function testFindByIdReturnsNullWhenNotExists(): void
    {
        $user = $this->repository->findById(999999);

        self::assertNull($user);
    }

    public function testFindByEmailReturnsNullWhenNotExists(): void
    {
        $user = $this->repository->findByEmail('nonexistent@example.com');

        self::assertNull($user);
    }

    public function testSaveAndFindById(): void
    {
        $user = new User('test-repo@example.com', ['ROLE_USER'], 'password123');

        $this->repository->save($user);

        $userId = $user->getId();
        self::assertNotNull($userId);
        $foundUser = $this->repository->findById($userId);

        self::assertInstanceOf(User::class, $foundUser);
        self::assertSame('test-repo@example.com', $foundUser->getEmail());

        // Cleanup
        $this->repository->remove($foundUser);
    }

    public function testSaveAndFindByEmail(): void
    {
        $user = new User('test-email@example.com', ['ROLE_ADMIN'], 'password456');

        $this->repository->save($user);

        $foundUser = $this->repository->findByEmail('test-email@example.com');

        self::assertInstanceOf(User::class, $foundUser);
        self::assertContains('ROLE_ADMIN', $foundUser->getRoles());

        // Cleanup
        $this->repository->remove($foundUser);
    }

    public function testFindAllIncludesCreatedUser(): void
    {
        $initialCount = \count($this->repository->findAll());

        $user = new User('test-findall@example.com', ['ROLE_USER'], 'password789');
        $this->repository->save($user);

        $users = $this->repository->findAll();

        self::assertCount($initialCount + 1, $users);
        self::assertContainsOnlyInstancesOf(User::class, $users);

        // Cleanup
        $this->repository->remove($user);
    }

    public function testRemoveDeletesUser(): void
    {
        $user = new User('test-remove@example.com', ['ROLE_USER'], 'password000');
        $this->repository->save($user);

        $userId = $user->getId();
        self::assertNotNull($userId);
        self::assertNotNull($this->repository->findById($userId));

        $this->repository->remove($user);

        self::assertNull($this->repository->findById($userId));
    }
}
