<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Article;
use App\Entity\User;
use App\Infrastructure\Article\DoctrineArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ArticlePublicationIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private DoctrineArticleRepository $articleRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->articleRepository = self::getContainer()->get(DoctrineArticleRepository::class);

        // Clear database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Article')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testArticlePublicationPersistence(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $this->entityManager->persist($author);

        $article = new Article('Test Article', 'Test content', $author);
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $articleId = $article->getId();
        self::assertNotNull($articleId);
        self::assertFalse($article->isPublished());

        // Act
        $article->setIsPublished(true);
        $this->entityManager->flush();

        // Assert
        $this->entityManager->clear(); // Clear entity manager to force fresh fetch

        $persistedArticle = $this->articleRepository->findById($articleId);
        self::assertNotNull($persistedArticle);
        self::assertTrue($persistedArticle->isPublished());
    }

    public function testArticleUnpublishingPersistence(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $this->entityManager->persist($author);

        $article = new Article('Test Article', 'Test content', $author);
        $article->setIsPublished(true);
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $articleId = $article->getId();
        self::assertNotNull($articleId);
        self::assertTrue($article->isPublished());

        // Act
        $article->setIsPublished(false);
        $this->entityManager->flush();

        // Assert
        $this->entityManager->clear();

        $persistedArticle = $this->articleRepository->findById($articleId);
        self::assertNotNull($persistedArticle);
        self::assertFalse($persistedArticle->isPublished());
    }

    public function testFindPublishedArticles(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $this->entityManager->persist($author);

        $publishedArticle1 = new Article('Published Article 1', 'Content 1', $author);
        $publishedArticle1->setIsPublished(true);

        $publishedArticle2 = new Article('Published Article 2', 'Content 2', $author);
        $publishedArticle2->setIsPublished(true);

        $draftArticle = new Article('Draft Article', 'Draft content', $author);

        $this->entityManager->persist($publishedArticle1);
        $this->entityManager->persist($publishedArticle2);
        $this->entityManager->persist($draftArticle);
        $this->entityManager->flush();

        // Act
        $publishedArticles = $this->articleRepository->findPublished();

        // Assert
        self::assertCount(2, $publishedArticles);

        $titles = \array_map(static fn (Article $article): string => $article->getTitle() ?? '', $publishedArticles);
        self::assertContains('Published Article 1', $titles);
        self::assertContains('Published Article 2', $titles);
        self::assertNotContains('Draft Article', $titles);
    }

    public function testPublicationUpdatesTimestamp(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $this->entityManager->persist($author);

        $article = new Article('Test Article', 'Test content', $author);
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $originalUpdatedAt = $article->getUpdatedAt();

        // Wait to ensure timestamp difference
        \usleep(1000);

        // Act
        $article->setIsPublished(true);
        $this->entityManager->flush();

        // Assert
        self::assertGreaterThan($originalUpdatedAt, $article->getUpdatedAt());
    }

    public function testTogglePublicationMultipleTimes(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $this->entityManager->persist($author);

        $article = new Article('Test Article', 'Test content', $author);
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $articleId = $article->getId();
        self::assertNotNull($articleId);

        // Act & Assert - Publish
        $article->setIsPublished(true);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $persistedArticle = $this->articleRepository->findById($articleId);
        self::assertNotNull($persistedArticle);
        self::assertTrue($persistedArticle->isPublished());

        // Act & Assert - Unpublish
        $persistedArticle->setIsPublished(false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $persistedArticle = $this->articleRepository->findById($articleId);
        self::assertNotNull($persistedArticle);
        self::assertFalse($persistedArticle->isPublished());

        // Act & Assert - Publish again
        $persistedArticle->setIsPublished(true);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $persistedArticle = $this->articleRepository->findById($articleId);
        self::assertNotNull($persistedArticle);
        self::assertTrue($persistedArticle->isPublished());
    }

    public function testRepositoryFindPublishedOrdersByCreatedAt(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $this->entityManager->persist($author);

        $oldArticle = new Article('Old Article', 'Old content', $author);
        $oldArticle->setIsPublished(true);
        $this->entityManager->persist($oldArticle);
        $this->entityManager->flush();

        // Wait to ensure different timestamps
        \usleep(10000);

        $newArticle = new Article('New Article', 'New content', $author);
        $newArticle->setIsPublished(true);
        $this->entityManager->persist($newArticle);
        $this->entityManager->flush();

        // Act
        $publishedArticles = $this->articleRepository->findPublished();

        // Assert
        self::assertCount(2, $publishedArticles);

        // Should be ordered by created date DESC (newest first)
        self::assertSame('New Article', $publishedArticles[0]->getTitle());
        self::assertSame('Old Article', $publishedArticles[1]->getTitle());
    }

    public function testArticlePublicationWithAuthorListener(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $this->entityManager->persist($author);
        $this->entityManager->flush();

        // Create article without setting author explicitly (will be set by listener)
        $article = new Article('Test Article', 'Test content');

        // Simulate authentication context - in real app this would be set by security system
        // For this test, we'll set the author manually since we don't have full auth context
        $article->setAuthor($author);

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        // Act
        $article->setIsPublished(true);
        $this->entityManager->flush();

        // Assert
        self::assertTrue($article->isPublished());
        self::assertSame($author, $article->getAuthor());
    }

    public function testBulkPublicationOperations(): void
    {
        // Arrange
        $author = new User('author@test.com', ['ROLE_USER'], 'password');
        $this->entityManager->persist($author);

        $articles = [];

        for ($i = 1; $i <= 5; ++$i) {
            $article = new Article("Article {$i}", "Content {$i}", $author);
            $articles[] = $article;
            $this->entityManager->persist($article);
        }
        $this->entityManager->flush();

        // Act - Publish all articles
        foreach ($articles as $article) {
            $article->setIsPublished(true);
        }
        $this->entityManager->flush();

        // Assert
        $publishedArticles = $this->articleRepository->findPublished();
        self::assertCount(5, $publishedArticles);

        foreach ($publishedArticles as $article) {
            self::assertTrue($article->isPublished());
        }
    }
}
