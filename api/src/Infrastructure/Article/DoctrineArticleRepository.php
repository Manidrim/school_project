<?php

declare(strict_types=1);

namespace App\Infrastructure\Article;

use App\Domain\Article\ArticleRepositoryInterface;
use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DoctrineArticleRepository extends ServiceEntityRepository implements ArticleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findById(int $id): ?Article
    {
        $result = $this->find($id);

        return $result instanceof Article ? $result : null;
    }

    public function findByTitle(string $title): ?Article
    {
        $result = $this->findOneBy(['title' => $title]);

        return $result instanceof Article ? $result : null;
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function findByAuthor(int $authorId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.author = :authorId')
            ->setParameter('authorId', $authorId)
            ->orderBy('a.createdAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findPublished(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('a.createdAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(Article $article): void
    {
        $this->getEntityManager()->persist($article);
        $this->getEntityManager()->flush();
    }

    public function remove(Article $article): void
    {
        $this->getEntityManager()->remove($article);
        $this->getEntityManager()->flush();
    }
}
