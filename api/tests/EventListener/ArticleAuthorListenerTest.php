<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Article;
use App\Entity\User;
use App\EventListener\ArticleAuthorListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 *
 * @covers \App\EventListener\ArticleAuthorListener
 */
final class ArticleAuthorListenerTest extends TestCase
{
    private ArticleAuthorListener $listener;

    private MockObject&Security $security;

    private EntityManagerInterface&MockObject $entityManager;

    private EntityRepository&MockObject $userRepository;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(EntityRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository)
        ;

        $this->listener = new ArticleAuthorListener(
            $this->security,
            $this->entityManager,
        );
    }

    public function testPrePersistSetsAuthorWhenEmpty(): void
    {
        // Arrange
        $user = new User('admin@test.com', ['ROLE_ADMIN'], 'hashedpassword');
        $user->setId(1);

        $userMock = $this->createMock(UserInterface::class);
        $userMock->method('getUserIdentifier')->willReturn('admin@test.com');

        $this->security->method('getUser')->willReturn($userMock);
        $this->userRepository->method('findOneBy')
            ->with(['email' => 'admin@test.com'])
            ->willReturn($user)
        ;

        $article = new Article('Test Title', 'Test Content');
        $event = $this->createMock(LifecycleEventArgs::class);

        // Act
        $this->listener->prePersist($article, $event);

        // Assert
        self::assertSame($user, $article->getAuthor());
    }

    public function testPrePersistDoesNotOverrideExistingAuthor(): void
    {
        // Arrange
        $existingAuthor = new User('existing@test.com', ['ROLE_USER'], 'password');
        $existingAuthor->setId(2);

        $article = new Article('Test Title', 'Test Content', $existingAuthor);
        $event = $this->createMock(LifecycleEventArgs::class);

        // Act
        $this->listener->prePersist($article, $event);

        // Assert
        self::assertSame($existingAuthor, $article->getAuthor());
    }

    public function testPrePersistWithNoAuthenticatedUser(): void
    {
        // Arrange
        $this->security->method('getUser')->willReturn(null);

        $article = new Article('Test Title', 'Test Content');
        $event = $this->createMock(LifecycleEventArgs::class);

        // Act
        $this->listener->prePersist($article, $event);

        // Assert
        self::assertNull($article->getAuthor());
    }

    public function testPreUpdateSetsLastModifiedBy(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $author->setId(1);

        $modifier = new User('modifier@test.com', ['ROLE_ADMIN'], 'password');
        $modifier->setId(2);

        $userMock = $this->createMock(UserInterface::class);
        $userMock->method('getUserIdentifier')->willReturn('modifier@test.com');

        $this->security->method('getUser')->willReturn($userMock);
        $this->userRepository->method('findOneBy')
            ->with(['email' => 'modifier@test.com'])
            ->willReturn($modifier)
        ;

        $article = new Article('Test Title', 'Test Content', $author);
        $event = $this->createMock(LifecycleEventArgs::class);

        // Act
        $this->listener->preUpdate($article, $event);

        // Assert
        self::assertSame($modifier, $article->getLastModifiedBy());
        self::assertSame($author, $article->getAuthor()); // Original author unchanged
    }

    public function testPreUpdateWithNoAuthenticatedUser(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $author->setId(1);

        $this->security->method('getUser')->willReturn(null);

        $article = new Article('Test Title', 'Test Content', $author);
        $event = $this->createMock(LifecycleEventArgs::class);

        // Act
        $this->listener->preUpdate($article, $event);

        // Assert
        self::assertNull($article->getLastModifiedBy());
    }
}
