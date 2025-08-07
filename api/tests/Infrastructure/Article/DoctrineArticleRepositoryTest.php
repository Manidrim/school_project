<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Article;

use App\Entity\Article;
use App\Entity\User;
use App\Infrastructure\Article\DoctrineArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Infrastructure\Article\DoctrineArticleRepository
 */
final class DoctrineArticleRepositoryTest extends KernelTestCase
{
    private DoctrineArticleRepository $repository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $repository = $container->get(DoctrineArticleRepository::class);
        \assert($repository instanceof DoctrineArticleRepository);
        $this->repository = $repository;

        $entityManager = $container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Article')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testFindByIdReturnsNullWhenNotExists(): void
    {
        $article = $this->repository->findById(999999);

        self::assertNull($article);
    }

    public function testFindByTitleReturnsNullWhenNotExists(): void
    {
        $article = $this->repository->findByTitle('Nonexistent Article');

        self::assertNull($article);
    }

    public function testSaveAndFindById(): void
    {
        $author = $this->createPersistedUser('author-repo@example.com');
        $article = new Article('Test Repository Article', 'Content for testing', $author);

        $this->repository->save($article);

        $articleId = $article->getId();
        self::assertNotNull($articleId);
        $foundArticle = $this->repository->findById($articleId);

        self::assertNotNull($foundArticle);
        self::assertSame($article->getTitle(), $foundArticle->getTitle());
        self::assertSame($article->getContent(), $foundArticle->getContent());
    }

    public function testSaveAndFindByTitle(): void
    {
        $author = $this->createPersistedUser('author-title@example.com');
        $title = 'Unique Title for Testing';
        $article = new Article($title, 'Content for title testing', $author);

        $this->repository->save($article);

        $foundArticle = $this->repository->findByTitle($title);

        self::assertNotNull($foundArticle);
        self::assertSame($title, $foundArticle->getTitle());
        self::assertSame($article->getContent(), $foundArticle->getContent());
    }

    public function testFindByAuthorReturnsCorrectArticles(): void
    {
        $author = $this->createPersistedUser('author-find@example.com');
        $otherAuthor = $this->createPersistedUser('other-author@example.com');

        $article1 = new Article('Article by Author 1', 'Content 1', $author);
        $article2 = new Article('Article by Author 2', 'Content 2', $author);
        $article3 = new Article('Article by Other Author', 'Content 3', $otherAuthor);

        $this->repository->save($article1);
        $this->repository->save($article2);
        $this->repository->save($article3);

        $authorId = $author->getId();
        self::assertNotNull($authorId);

        $articles = $this->repository->findByAuthor($authorId);

        self::assertCount(2, $articles);
        self::assertContains($article1, $articles);
        self::assertContains($article2, $articles);
        self::assertNotContains($article3, $articles);
    }

    public function testFindPublishedReturnsOnlyPublishedArticles(): void
    {
        $author = $this->createPersistedUser('author-published@example.com');

        $publishedArticle = new Article('Published Article', 'Published content', $author);
        $publishedArticle->publish();

        $unpublishedArticle = new Article('Unpublished Article', 'Unpublished content', $author);

        $this->repository->save($publishedArticle);
        $this->repository->save($unpublishedArticle);

        $publishedArticles = $this->repository->findPublished();

        self::assertGreaterThanOrEqual(1, \count($publishedArticles));

        $foundPublished = false;
        $foundUnpublished = false;

        foreach ($publishedArticles as $article) {
            if ($article->getId() === $publishedArticle->getId()) {
                $foundPublished = true;
            }

            if ($article->getId() === $unpublishedArticle->getId()) {
                $foundUnpublished = true;
            }
        }

        self::assertTrue($foundPublished);
        self::assertFalse($foundUnpublished);
    }

    public function testRemoveDeletesArticle(): void
    {
        $author = $this->createPersistedUser('author-remove@example.com');
        $article = new Article('Article to Remove', 'Content to remove', $author);

        $this->repository->save($article);

        $articleId = $article->getId();
        self::assertNotNull($articleId);

        $foundBeforeRemove = $this->repository->findById($articleId);
        self::assertNotNull($foundBeforeRemove);

        $this->repository->remove($article);

        $foundAfterRemove = $this->repository->findById($articleId);
        self::assertNull($foundAfterRemove);
    }

    public function testFindAllReturnsAllArticles(): void
    {
        $author = $this->createPersistedUser('author-all@example.com');
        $article = new Article('Article for Find All', 'Content for find all', $author);

        $this->repository->save($article);

        $allArticles = $this->repository->findAll();

        self::assertGreaterThanOrEqual(1, \count($allArticles));
        self::assertContains($article, $allArticles);
    }

    /**
     * @param array<string> $roles
     */
    private function createPersistedUser(string $email, array $roles = ['ROLE_USER']): User
    {
        $user = new User($email, $roles, 'password123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
