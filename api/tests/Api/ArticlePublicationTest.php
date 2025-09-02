<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Article;
use App\Entity\User;
use App\Tests\E2E\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversNothing
 */
final class ArticlePublicationTest extends ApiTestCase
{
    public function testPatchArticleToPublish(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $article = $this->createArticle($admin, false); // Create unpublished article

        $this->loginAs($admin);

        // Act
        $jsonContent = \json_encode(['isPublished' => true]);
        self::assertNotFalse($jsonContent);

        $this->client->request('PATCH', "/api/articles/{$article->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $jsonContent);

        // Assert
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $responseData = \json_decode($content, true);
        self::assertIsArray($responseData);

        self::assertTrue($responseData['isPublished']);
        self::assertSame($article->getTitle(), $responseData['title']);
        self::assertSame($article->getContent(), $responseData['content']);
    }

    public function testPatchArticleToUnpublish(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $article = $this->createArticle($admin, true); // Create published article

        $this->loginAs($admin);

        // Act
        $jsonContent = \json_encode(['isPublished' => false]);
        self::assertNotFalse($jsonContent);

        $this->client->request('PATCH', "/api/articles/{$article->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $jsonContent);

        // Assert
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $responseData = \json_decode($content, true);
        self::assertIsArray($responseData);

        self::assertFalse($responseData['isPublished']);
    }

    public function testPatchArticlePublicationUpdatesTimestamp(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $article = $this->createArticle($admin, false);
        $originalUpdatedAt = $article->getUpdatedAt();

        $this->loginAs($admin);

        // Wait to ensure timestamp difference
        \usleep(1000);

        // Act
        $jsonContent = \json_encode(['isPublished' => true]);
        self::assertNotFalse($jsonContent);

        $this->client->request('PATCH', "/api/articles/{$article->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $jsonContent);

        // Assert
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Reload article from database
        $articleId = $article->getId();
        self::assertNotNull($articleId);
        $article = $this->entityManager->find(Article::class, $articleId);
        self::assertNotNull($article);
        self::assertNotNull($article->getUpdatedAt());
    }

    public function testPatchArticlePublicationWithoutAuthentication(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $article = $this->createArticle($admin, false);

        // Act
        $jsonContent = \json_encode(['isPublished' => true]);
        self::assertNotFalse($jsonContent);

        $this->client->request('PATCH', "/api/articles/{$article->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $jsonContent);

        // Assert
        self::assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode()); // Redirect to login
    }

    public function testPatchArticlePublicationAsRegularUser(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $user = $this->createUser('user@test.com', ['ROLE_USER']);
        $article = $this->createArticle($admin, false);

        $this->loginAs($user, 'password123');

        // Act
        $jsonContent = \json_encode(['isPublished' => true]);
        self::assertNotFalse($jsonContent);

        $this->client->request('PATCH', "/api/articles/{$article->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $jsonContent);

        // Assert
        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPatchNonExistentArticle(): void
    {
        // Arrange
        $admin = $this->createAdminUser();

        $this->loginAs($admin);

        // Act
        $jsonContent = \json_encode(['isPublished' => true]);
        self::assertNotFalse($jsonContent);

        $this->client->request('PATCH', '/api/articles/999999', [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $jsonContent);

        // Assert
        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testPatchArticleWithInvalidData(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $article = $this->createArticle($admin, false);

        $this->loginAs($admin);

        // Act
        $jsonContent = \json_encode(['isPublished' => 'invalid']);
        self::assertNotFalse($jsonContent);

        $this->client->request('PATCH', "/api/articles/{$article->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $jsonContent);

        // Assert
        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPatchArticleOnlyChangesSpecifiedFields(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $article = $this->createArticle($admin, false);
        $originalTitle = $article->getTitle();
        $originalContent = $article->getContent();

        $this->loginAs($admin);

        // Act
        $jsonContent = \json_encode(['isPublished' => true]);
        self::assertNotFalse($jsonContent);

        $this->client->request('PATCH', "/api/articles/{$article->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $jsonContent);

        // Assert
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $responseData = \json_decode($content, true);
        self::assertIsArray($responseData);

        self::assertTrue($responseData['isPublished']);
        self::assertSame($originalTitle, $responseData['title']);
        self::assertSame($originalContent, $responseData['content']);
    }

    private function createArticle(User $author, bool $isPublished): Article
    {
        $article = new Article('Test Article', 'Test content for article', $author);

        if ($isPublished) {
            $article->setIsPublished(true);
        }

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $article;
    }
}
