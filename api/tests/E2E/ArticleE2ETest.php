<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Entity\Article;

/**
 * @internal
 *
 * @coversNothing
 */
final class ArticleE2ETest extends ApiTestCase
{
    public function testGetEmptyArticleCollection(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Act
        $this->makeJsonRequest('GET', '/api/articles');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame(0, $data['hydra:totalItems']);
        self::assertEmpty($data['hydra:member']);
    }

    public function testCreateArticle(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        $articleData = [
            'title' => 'Test Article E2E',
            'content' => 'This is a test article created via E2E test.',
            'isPublished' => true,
        ];

        // Act
        $this->makeJsonRequest('POST', '/api/articles', $articleData);

        // Assert
        $this->assertApiResponseStatusCodeSame(201);
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame($articleData['title'], $data['title']);
        self::assertSame($articleData['content'], $data['content']);
        self::assertTrue($data['isPublished']);
        self::assertNotNull($data['author']);
        self::assertNotNull($data['createdAt']);
        self::assertNotNull($data['updatedAt']);
        self::assertIsInt($data['id']);
    }

    public function testCreateArticleWithoutAuthentication(): void
    {
        // Act
        $this->makeJsonRequest('POST', '/api/articles', [
            'title' => 'Unauthorized Article',
            'content' => 'This should fail.',
            'isPublished' => false,
        ]);

        // Assert
        $this->assertApiResponseStatusCodeSame(302); // Redirect to login
    }

    public function testCreateArticleWithInvalidData(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Act - missing required title
        $this->makeJsonRequest('POST', '/api/articles', [
            'content' => 'Article without title',
            'isPublished' => false,
        ]);

        // Assert
        $this->assertApiResponseStatusCodeSame(422);
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame('ConstraintViolationList', $data['@type']);
        self::assertNotEmpty($data['violations']);
    }

    public function testGetArticleById(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Create article first
        $this->makeJsonRequest('POST', '/api/articles', [
            'title' => 'Article to Retrieve',
            'content' => 'Content for retrieval test.',
            'isPublished' => true,
        ]);

        $createData = $this->decodeJsonResponse();
        $articleId = $createData['id'];
        self::assertIsNumeric($articleId);

        // Act
        $this->makeJsonRequest('GET', "/api/articles/{$articleId}");

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame($articleId, $data['id']);
        self::assertSame('Article to Retrieve', $data['title']);
        self::assertSame('Content for retrieval test.', $data['content']);
    }

    public function testUpdateArticle(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Create article first
        $this->makeJsonRequest('POST', '/api/articles', [
            'title' => 'Original Title',
            'content' => 'Original content.',
            'isPublished' => false,
        ]);

        $createData = $this->decodeJsonResponse();
        $articleId = $createData['id'];
        self::assertIsNumeric($articleId);

        // Act
        $this->makeJsonRequest('PUT', "/api/articles/{$articleId}", [
            'title' => 'Updated Title',
            'content' => 'Updated content.',
            'isPublished' => true,
        ]);

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame($articleId, $data['id']);
        self::assertSame('Updated Title', $data['title']);
        self::assertSame('Updated content.', $data['content']);
        self::assertTrue($data['isPublished']);
        self::assertNotNull($data['lastModifiedBy']);
    }

    public function testPatchArticle(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Create article first
        $this->makeJsonRequest('POST', '/api/articles', [
            'title' => 'Article to Patch',
            'content' => 'Original content.',
            'isPublished' => false,
        ]);

        $createData = $this->decodeJsonResponse();
        $articleId = $createData['id'];
        self::assertIsNumeric($articleId);

        // Act - only update publication status
        $this->client->request('PATCH', "/api/articles/{$articleId}", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $this->encodeJson(['isPublished' => true]));

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame($articleId, $data['id']);
        self::assertSame('Article to Patch', $data['title']); // Unchanged
        self::assertTrue($data['isPublished']); // Changed
    }

    public function testDeleteArticle(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Create article first
        $this->makeJsonRequest('POST', '/api/articles', [
            'title' => 'Article to Delete',
            'content' => 'This will be deleted.',
            'isPublished' => true,
        ]);

        $createData = $this->decodeJsonResponse();
        $articleId = $createData['id'];
        self::assertIsNumeric($articleId);

        // Act
        $this->client->request('DELETE', "/api/articles/{$articleId}");

        // Assert
        $this->assertApiResponseStatusCodeSame(204);

        // Verify article is deleted
        $this->makeJsonRequest('GET', "/api/articles/{$articleId}");
        $this->assertApiResponseStatusCodeSame(404);
    }

    public function testGetArticleCollection(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Create multiple articles
        $articles = [
            ['title' => 'First Article', 'content' => 'First content', 'isPublished' => true],
            ['title' => 'Second Article', 'content' => 'Second content', 'isPublished' => false],
            ['title' => 'Third Article', 'content' => 'Third content', 'isPublished' => true],
        ];

        foreach ($articles as $articleData) {
            $this->makeJsonRequest('POST', '/api/articles', $articleData);
            $this->assertApiResponseStatusCodeSame(201);
        }

        // Act
        $this->makeJsonRequest('GET', '/api/articles');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame(3, $data['hydra:totalItems']);
        self::assertIsArray($data['hydra:member']);
        self::assertCount(3, $data['hydra:member']);

        // Verify each article has required fields
        foreach ($data['hydra:member'] as $article) {
            self::assertIsArray($article);
            self::assertArrayHasKey('id', $article);
            self::assertArrayHasKey('title', $article);
            self::assertArrayHasKey('content', $article);
            self::assertArrayHasKey('author', $article);
            self::assertArrayHasKey('createdAt', $article);
            self::assertArrayHasKey('updatedAt', $article);
        }
    }

    public function testRegularUserCannotModifyArticles(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $user = $this->createUser('user@test.com', ['ROLE_USER']);

        // Admin creates an article
        $this->loginAs($admin);
        $this->makeJsonRequest('POST', '/api/articles', [
            'title' => 'Admin Article',
            'content' => 'Created by admin.',
            'isPublished' => true,
        ]);
        $createData = $this->decodeJsonResponse();
        $articleId = $createData['id'];
        self::assertIsNumeric($articleId);

        // User tries to modify it
        $this->logout();
        $this->loginAs($user, 'password123');

        // Act & Assert - User cannot create articles
        $this->makeJsonRequest('POST', '/api/articles', [
            'title' => 'User Article',
            'content' => 'Should fail.',
            'isPublished' => false,
        ]);
        $this->assertApiResponseStatusCodeSame(403);

        // Act & Assert - User cannot update articles
        $this->makeJsonRequest('PUT', "/api/articles/{$articleId}", [
            'title' => 'Modified by User',
            'content' => 'Should fail.',
            'isPublished' => false,
        ]);
        $this->assertApiResponseStatusCodeSame(403);

        // Act & Assert - User cannot delete articles
        $this->client->request('DELETE', "/api/articles/{$articleId}");
        $this->assertApiResponseStatusCodeSame(403);

        // But user can read articles
        $this->makeJsonRequest('GET', '/api/articles');
        $this->assertApiResponseIsSuccessful();
    }
}
