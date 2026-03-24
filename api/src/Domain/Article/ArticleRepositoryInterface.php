<?php

declare(strict_types=1);

namespace App\Domain\Article;

use App\Entity\Article;

interface ArticleRepositoryInterface
{
    public function findById(int $id): ?Article;

    public function findByTitle(string $title): ?Article;

    /**
     * @return Article[]
     */
    public function findAll(): array;

    /**
     * @return Article[]
     */
    public function findByAuthor(int $authorId): array;

    /**
     * @return Article[]
     */
    public function findPublished(): array;

    public function save(Article $article): void;

    public function remove(Article $article): void;
}
