<?php

declare(strict_types=1);

namespace App\Tests\Domain\Article;

use App\Entity\Article;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Entity\Article
 */
final class ArticleTest extends TestCase
{
    public function testCreateArticle(): void
    {
        // Arrange
        $title = 'Test Article';
        $content = 'This is test content';
        $author = new User('author@test.com', ['ROLE_USER'], 'password');

        // Act
        $article = new Article($title, $content, $author);

        // Assert
        self::assertSame($title, $article->getTitle());
        self::assertSame($content, $article->getContent());
        self::assertSame($author, $article->getAuthor());
        self::assertInstanceOf(\DateTimeImmutable::class, $article->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $article->getUpdatedAt());
        self::assertFalse($article->isPublished());
        self::assertNull($article->getLastModifiedBy());
    }

    public function testCreateArticleWithoutAuthor(): void
    {
        // Act
        $article = new Article('Test Title', 'Test Content');

        // Assert
        self::assertSame('Test Title', $article->getTitle());
        self::assertSame('Test Content', $article->getContent());
        self::assertNull($article->getAuthor());
    }

    public function testSetTitle(): void
    {
        // Arrange
        $article = new Article('Original Title', 'Content');
        $originalUpdatedAt = $article->getUpdatedAt();

        // Wait a small amount to ensure timestamp difference
        \usleep(1000);

        // Act
        $article->setTitle('New Title');

        // Assert
        self::assertSame('New Title', $article->getTitle());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testSetContent(): void
    {
        // Arrange
        $article = new Article('Title', 'Original Content');

        // Act
        $article->setContent('New Content');

        // Assert
        self::assertSame('New Content', $article->getContent());
    }

    public function testSetAuthor(): void
    {
        // Arrange
        $article = new Article('Title', 'Content');
        $author = new User('author@test.com', ['ROLE_USER'], 'password');

        // Act
        $article->setAuthor($author);

        // Assert
        self::assertSame($author, $article->getAuthor());
    }

    public function testSetLastModifiedBy(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $modifier = new User('modifier@test.com', ['ROLE_ADMIN'], 'password');
        $article = new Article('Title', 'Content', $author);
        $originalUpdatedAt = $article->getUpdatedAt();

        // Wait a small amount to ensure timestamp difference
        \usleep(1000);

        // Act
        $article->setLastModifiedBy($modifier);

        // Assert
        self::assertSame($modifier, $article->getLastModifiedBy());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testPublish(): void
    {
        // Arrange
        $article = new Article('Title', 'Content');
        $originalUpdatedAt = $article->getUpdatedAt();

        // Wait a small amount to ensure timestamp difference
        \usleep(1000);

        // Act
        $result = $article->publish();

        // Assert
        self::assertSame($article, $result); // Fluent interface
        self::assertTrue($article->isPublished());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testUnpublish(): void
    {
        // Arrange
        $article = new Article('Title', 'Content');
        $article->publish();
        $originalUpdatedAt = $article->getUpdatedAt();

        // Wait a small amount to ensure timestamp difference
        \usleep(1000);

        // Act
        $result = $article->unpublish();

        // Assert
        self::assertSame($article, $result); // Fluent interface
        self::assertFalse($article->isPublished());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testIdIsNullByDefault(): void
    {
        // Arrange & Act
        $article = new Article('Title', 'Content');

        // Assert
        self::assertNull($article->getId());
    }

    public function testTimestampsAreCorrectlyInitialized(): void
    {
        // Arrange
        $before = new \DateTimeImmutable();

        // Act
        $article = new Article('Title', 'Content');

        $after = new \DateTimeImmutable();

        // Assert
        self::assertGreaterThanOrEqual($before, $article->getCreatedAt());
        self::assertLessThanOrEqual($after, $article->getCreatedAt());
        self::assertGreaterThanOrEqual($before, $article->getUpdatedAt());
        self::assertLessThanOrEqual($after, $article->getUpdatedAt());
    }

    public function testCreatedAtNeverChanges(): void
    {
        // Arrange
        $article = new Article('Title', 'Content');
        $originalCreatedAt = $article->getCreatedAt();

        // Wait a small amount
        \usleep(1000);

        // Act
        $article->setTitle('New Title');
        $article->publish();
        $article->setLastModifiedBy(new User('test@test.com', ['ROLE_USER'], 'password'));

        // Assert
        self::assertSame($originalCreatedAt, $article->getCreatedAt());
    }

    public function testSetIsPublishedTrue(): void
    {
        // Arrange
        $article = new Article('Title', 'Content');
        $originalUpdatedAt = $article->getUpdatedAt();

        // Wait a small amount to ensure timestamp difference
        \usleep(1000);

        // Act
        $result = $article->setIsPublished(true);

        // Assert
        self::assertSame($article, $result); // Fluent interface
        self::assertTrue($article->isPublished());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testSetIsPublishedFalse(): void
    {
        // Arrange
        $article = new Article('Title', 'Content');
        $article->setIsPublished(true);
        $originalUpdatedAt = $article->getUpdatedAt();

        // Wait a small amount to ensure timestamp difference
        \usleep(1000);

        // Act
        $result = $article->setIsPublished(false);

        // Assert
        self::assertSame($article, $result); // Fluent interface
        self::assertFalse($article->isPublished());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testSetIsPublishedUpdatesTimestamp(): void
    {
        // Arrange
        $article = new Article('Title', 'Content');
        $originalUpdatedAt = $article->getUpdatedAt();

        // Wait a small amount to ensure timestamp difference
        \usleep(1000);

        // Act
        $article->setIsPublished(true);

        // Assert
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }
}
