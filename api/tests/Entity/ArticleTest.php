<?php

declare(strict_types=1);

namespace App\Tests\Entity;

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
    public function testNewArticleHasNullId(): void
    {
        $article = new Article();
        self::assertNull($article->getId());
    }

    public function testConstructorSetsPropertiesCorrectly(): void
    {
        $title = 'Test Article';
        $content = 'This is test content';
        $author = new User('author@example.com', ['ROLE_USER'], 'password');

        $article = new Article($title, $content, $author);

        self::assertSame($title, $article->getTitle());
        self::assertSame($content, $article->getContent());
        self::assertSame($author, $article->getAuthor());
        self::assertInstanceOf(\DateTimeImmutable::class, $article->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $article->getUpdatedAt());
        self::assertFalse($article->isPublished());
    }

    public function testSetAndGetTitle(): void
    {
        $article = new Article();
        $title = 'New Title';

        $result = $article->setTitle($title);

        self::assertSame($article, $result);
        self::assertSame($title, $article->getTitle());
    }

    public function testSetAndGetContent(): void
    {
        $article = new Article();
        $content = 'New content for the article';

        $result = $article->setContent($content);

        self::assertSame($article, $result);
        self::assertSame($content, $article->getContent());
    }

    public function testGetAuthorReturnsSetAuthor(): void
    {
        $author = new User('author@example.com', ['ROLE_USER'], 'password');
        $article = new Article('Test Title', 'Test Content', $author);

        self::assertSame($author, $article->getAuthor());
    }

    public function testSetAndGetLastModifiedBy(): void
    {
        $article = new Article();
        $editor = new User('editor@example.com', ['ROLE_ADMIN'], 'password');

        $result = $article->setLastModifiedBy($editor);

        self::assertSame($article, $result);
        self::assertSame($editor, $article->getLastModifiedBy());
    }

    public function testSetTitleUpdatesModifiedTimestamp(): void
    {
        $article = new Article();
        $originalUpdatedAt = $article->getUpdatedAt();

        \usleep(1000);
        $article->setTitle('New Title');

        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testSetContentUpdatesModifiedTimestamp(): void
    {
        $article = new Article();
        $originalUpdatedAt = $article->getUpdatedAt();

        \usleep(1000);
        $article->setContent('New content');

        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testSetLastModifiedByUpdatesModifiedTimestamp(): void
    {
        $article = new Article();
        $originalUpdatedAt = $article->getUpdatedAt();
        $editor = new User('editor@example.com', ['ROLE_ADMIN'], 'password');

        \usleep(1000);
        $article->setLastModifiedBy($editor);

        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testPublishSetsIsPublishedToTrueAndUpdatesTimestamp(): void
    {
        $article = new Article();
        $originalUpdatedAt = $article->getUpdatedAt();

        \usleep(1000);
        $result = $article->publish();

        self::assertSame($article, $result);
        self::assertTrue($article->isPublished());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testUnpublishSetsIsPublishedToFalseAndUpdatesTimestamp(): void
    {
        $article = new Article();
        $article->publish();
        $originalUpdatedAt = $article->getUpdatedAt();

        \usleep(1000);
        $result = $article->unpublish();

        self::assertSame($article, $result);
        self::assertFalse($article->isPublished());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testPublishAfterUnpublishSetsCorrectState(): void
    {
        $article = new Article();
        $article->unpublish();
        $originalUpdatedAt = $article->getUpdatedAt();

        \usleep(1000);
        $result = $article->publish();

        self::assertSame($article, $result);
        self::assertTrue($article->isPublished());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testSetIsPublishedToTrue(): void
    {
        $article = new Article();
        $originalUpdatedAt = $article->getUpdatedAt();

        \usleep(1000);
        $result = $article->setIsPublished(true);

        self::assertSame($article, $result);
        self::assertTrue($article->isPublished());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testSetIsPublishedToFalse(): void
    {
        $article = new Article();
        $article->setIsPublished(true);
        $originalUpdatedAt = $article->getUpdatedAt();

        \usleep(1000);
        $result = $article->setIsPublished(false);

        self::assertSame($article, $result);
        self::assertFalse($article->isPublished());
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testSetIsPublishedToggleState(): void
    {
        $article = new Article();

        self::assertFalse($article->isPublished());

        $article->setIsPublished(true);
        self::assertTrue($article->isPublished());

        $article->setIsPublished(false);
        self::assertFalse($article->isPublished());

        $article->setIsPublished(true);
        self::assertTrue($article->isPublished());
    }
}
